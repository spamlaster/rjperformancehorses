// Shared helpers for the browser tests: local MySQL fixtures and the Laravel API.
// Needs `npm run api` and `npm run dev` (Vite proxies /api to Laravel).
import { execFileSync } from 'node:child_process'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

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

/** Complete first-time setup using the isolated test mail log. */
export async function setupRiderAccount(scope, email, password = 'Rider-test-password!123') {
  await scope.getByRole('button', { name: 'Create account', exact: true }).click()
  const emailField = scope.getByLabel('Email address', { exact: true })
  if (!await emailField.getAttribute('readonly')) {
    if (!await emailField.evaluate(element => element.readOnly)) await emailField.fill(email)
  }
  await scope.getByLabel('Password', { exact: true }).fill(password)
  await scope.getByLabel('Confirm password', { exact: true }).fill(password)
  await scope.getByRole('button', { name: 'Send Email Verification Code', exact: true }).click()
  await scope.getByText('Your email verification code has been sent.', { exact: true }).waitFor()
  const mailLog = readFileSync(new URL('../api/storage/logs/laravel.log', import.meta.url), 'utf8')
  const code = [...mailLog.matchAll(/Your email verification code is: (\d{6})/g)].at(-1)?.[1]
  assert(code, 'test email contains an account verification code')
  await scope.getByLabel('Email verification code', { exact: true }).fill(code)
  await scope.getByRole('button', { name: 'Verify Email and Continue', exact: true }).click()
}

/** Test-only fixture; the public form still performs the actual password sign-in. */
export function createVerifiedRider(email, password = 'Rider-test-password!123') {
  const hash = execFileSync('php', ['-r', 'echo password_hash($argv[1], PASSWORD_BCRYPT);', password], { encoding: 'utf8' }).trim()
  sql(`INSERT INTO users(email,password,email_verified_at,is_active) VALUES ('${email}','${hash}',NOW(),1)`)
}

export async function signInRider(page, email, password = 'Rider-test-password!123') {
  await page.getByLabel('Email address', { exact: true }).fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Sign In', exact: true }).click()
}
