// Shared helpers for the browser tests: local MySQL fixtures and the Laravel API.
// Needs `npm run api` and `npm run dev` (Vite proxies /api to Laravel).
import { execFileSync } from 'node:child_process'
import assert from 'node:assert/strict'

export const base = process.env.APP_URL || 'http://127.0.0.1:5173'
assert.match(base, /127\.0\.0\.1|localhost/, 'browser tests only run against a local app')

const db = { host: process.env.DB_HOST || '127.0.0.1', user: process.env.DB_USERNAME || 'rjph', password: process.env.DB_PASSWORD || 'rjph_local', database: process.env.DB_DATABASE || 'rjph' }

/** Run SQL against the local database; returns tab-separated rows. */
export const sql = s => execFileSync('mysql', ['-h', db.host, '-u', db.user, `-p${db.password}`, '-N', '-B', db.database, '-e', s], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] })

/** Public (anonymous) API call. */
export async function publicApi(path) {
  const response = await fetch(`${base}/api${path}`, { headers: { Accept: 'application/json' } })
  return { status: response.status, data: await response.json().catch(() => null) }
}

export const slots = async (lessonTypeId, date) => (await publicApi(`/availability/slots?lesson_type_id=${encodeURIComponent(lessonTypeId)}&date=${date}`)).data

/** Provision an admin login the same way production does. */
export function createAdmin(email, password) {
  execFileSync('php', ['artisan', 'admin:create', email, `--password=${password}`], { cwd: new URL('../api/', import.meta.url), stdio: 'ignore' })
}

export const deleteAdmin = email => sql(`DELETE FROM users WHERE email='${email}'`)
