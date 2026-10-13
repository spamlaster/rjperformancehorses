/**
 * Laravel API client
 *
 * The SPA and API share an origin (Nginx in production, the Vite proxy in
 * development), so Sanctum authenticates admins with session cookies.
 * Mutating requests carry the XSRF token Laravel sets in a cookie.
 */

export interface ApiResult<T> {
  ok: boolean
  status: number
  data: T | null
  error: string | null
}

function xsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : null
}

async function ensureCsrfCookie(force = false) {
  if (force || !xsrfToken()) {
    await fetch('/api/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

/** First validation message, else the server message. */
function errorMessage(body: any, status: number): string {
  const firstError = body?.errors && Object.values(body.errors).flat()[0]
  return (firstError as string) || body?.message || `Request failed (${status})`
}

export async function api<T = unknown>(method: string, path: string, body?: unknown, retried = false): Promise<ApiResult<T>> {
  const mutating = method !== 'GET'
  if (mutating) await ensureCsrfCookie()

  const headers: Record<string, string> = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  const token = xsrfToken()
  if (mutating && token) headers['X-XSRF-TOKEN'] = token

  let response: Response
  try {
    response = await fetch(`/api${path}`, {
      method,
      headers,
      credentials: 'include',
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch (err) {
    return { ok: false, status: 0, data: null, error: err instanceof Error ? err.message : 'Network error' }
  }

  // Expired CSRF token: refresh it once and retry.
  if (response.status === 419 && mutating && !retried) {
    await ensureCsrfCookie(true)
    return api<T>(method, path, body, true)
  }

  const json = await response.json().catch(() => null)
  return response.ok
    ? { ok: true, status: response.status, data: json as T, error: null }
    : { ok: false, status: response.status, data: null, error: errorMessage(json, response.status) }
}
