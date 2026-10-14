import { chromium } from '@playwright/test'
import { createServer } from 'vite'
import { execFileSync, spawn } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = fileURLToPath(new URL('../', import.meta.url))
const apiRoot = fileURLToPath(new URL('../api/', import.meta.url))
const env = {
  ...process.env,
  APP_ENV: 'testing', APP_DEBUG: 'false',
  APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
  DB_CONNECTION: 'mysql', DB_DATABASE: 'rjph_weekly_browser_test',
  DB_USERNAME: process.env.DB_USERNAME || 'rjph_test',
  DB_PASSWORD: process.env.DB_PASSWORD || 'rjph_test_local',
  DB_URL: '', CACHE_STORE: 'array', SESSION_DRIVER: 'file',
  MAIL_MAILER: 'log', LOG_CHANNEL: 'single', SANCTUM_STATEFUL_DOMAINS: '127.0.0.1:5197',
}
const artisan = (...args) => execFileSync('php', ['artisan', ...args], { cwd: apiRoot, env, stdio: 'pipe' })
let php, vite, browser
const runtime = []
const date = '2099-01-07' // Wednesday, the customer's example.
const nextWeek = '2099-01-14'
const base = 'http://127.0.0.1:5197'
const slots = async (day) => (await fetch(`${base}/api/availability/slots?lesson_type_id=lesson-1&date=${day}`)).json()
try {
  // This database name is fixed and dedicated to this test; never use the app's DB_DATABASE.
  artisan('migrate:fresh', '--seed', '--force', '--no-interaction')
  artisan('admin:create', 'weekly-admin@example.com', '--password=weekly-test-password')
  php = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8017'], { cwd: apiRoot, env, stdio: 'ignore', detached: true })
  for (let attempt = 0; attempt < 100; attempt++) {
    try {
      if ((await fetch('http://127.0.0.1:8017/api/up')).ok) break
    } catch {}
    if (attempt === 99) throw new Error('Test API failed to start')
    await new Promise(resolve => setTimeout(resolve, 100))
  }
  vite = await createServer({ root, server: { host: '127.0.0.1', port: 5197, strictPort: true, open: false, proxy: { '/api': 'http://127.0.0.1:8017' } } })
  await vite.listen()
  browser = await chromium.launch({ headless: true })
  const page = await browser.newPage()
  page.on('pageerror', error => runtime.push(error.message))
  page.on('dialog', dialog => dialog.accept())

  await page.goto(`${base}/book`)
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.locator('input[type=date]').first().fill(date)
  await page.getByRole('button', { name: '5:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.getByPlaceholder('John', { exact: true }).fill('Weekly')
  await page.getByPlaceholder('Doe', { exact: true }).first().fill('Customer')
  await page.getByPlaceholder('john@example.com').fill('weekly-customer@example.com')
  await page.getByLabel('I am the rider').check()
  await page.locator('input[type=date]').nth(1).fill('1990-01-01')
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.getByRole('button', { name: 'Reserve Weekly Lesson' }).click()
  await page.getByText('Your Weekly Lesson Is Reserved!').waitFor()
  const link = await page.locator('#management-link').inputValue()
  assert.match(link, /\/reservation#[a-f0-9]{64}$/)
  assert(!(await slots(nextWeek)).some(slot => slot.start_time === '17:00'))
  console.log('PASS: Wednesday 5pm booking holds following Wednesdays and returns a private management link')

  await page.goto(link)
  await page.getByRole('heading', { name: 'Upcoming sessions' }).waitFor()
  assert.match(await page.locator('body').innerText(), /Every Wednesday at 5:00 PM/)
  await page.getByRole('button', { name: /^Skip session on/ }).first().click()
  await page.getByText('Session skipped. Your weekly time is still reserved.').waitFor()
  assert(!(await slots(nextWeek)).some(slot => slot.start_time === '17:00'))
  await page.reload()
  await page.getByText('Skipped', { exact: true }).waitFor()
  console.log('PASS: skipping persists after refresh and keeps the standing weekly reservation')

  await page.getByRole('button', { name: 'Cancel Weekly Reservation', exact: true }).click()
  await page.getByText('Your weekly reservation is cancelled.', { exact: false }).waitFor()
  assert((await slots(nextWeek)).some(slot => slot.start_time === '17:00'))
  await page.reload()
  await page.getByText('Your weekly reservation is cancelled.', { exact: false }).waitFor()
  console.log('PASS: customer cancellation persists and releases future Wednesdays')

  const response = await page.request.post(`${base}/api/bookings/guest`, { data: {
    lesson_type_id: 'lesson-1', start_time: `${nextWeek}T17:00:00`, end_time: `${nextWeek}T17:30:00`,
    customer: { first_name: 'Replacement', last_name: 'Customer', email: 'replacement@example.com' },
    riders: [{ first_name: 'Replacement', last_name: 'Customer', date_of_birth: '1990-01-01' }],
  } })
  assert.equal(response.status(), 201)
  const replacement = await response.json()

  await page.request.post(`${base}/api/reservations/skip`, { data: { token: replacement.management_token, date: '2099-01-21' } })
  await page.clock.setFixedTime(new Date('2099-01-01T12:00:00Z'))
  await page.goto(`${base}/admin/login`)
  await page.getByPlaceholder('Enter email').fill('weekly-admin@example.com')
  await page.getByPlaceholder('Enter password').fill('weekly-test-password')
  await page.getByRole('button', { name: 'Login', exact: true }).click()
  await page.waitForURL('**/admin/dashboard')
  const calendar = page.getByRole('region', { name: 'Monthly booking calendar' })
  await calendar.getByRole('heading', { name: 'January 2099', exact: true }).waitFor()
  await calendar.getByRole('button', { name: 'Wednesday, January 14, 2099, 1 lesson', exact: true }).click()
  await calendar.getByText('5:00 PM–5:30 PM · Replacement Customer', { exact: true }).waitFor()
  assert.equal(await calendar.getByRole('button', { name: 'Wednesday, January 21, 2099, 0 lessons', exact: true }).count(), 1)
  for (let i = 0; i < 3; i++) await calendar.getByRole('button', { name: 'Next month', exact: true }).click()
  await calendar.getByRole('heading', { name: 'April 2099', exact: true }).waitFor()
  await calendar.getByRole('button', { name: 'Wednesday, April 8, 2099, 1 lesson', exact: true }).click()
  await calendar.getByText('5:00 PM–5:30 PM · Replacement Customer', { exact: true }).waitFor()
  await page.setViewportSize({ width: 390, height: 844 })
  assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'calendar fits a phone viewport')
  await calendar.getByRole('button', { name: 'Today', exact: true }).click()
  await calendar.getByRole('heading', { name: 'January 2099', exact: true }).waitFor()
  console.log('PASS: dashboard calendar shows day details, skips, and reservations beyond eight weeks on desktop and mobile')
  await page.setViewportSize({ width: 1280, height: 900 })
  await page.goto(`${base}/admin/bookings`)
  const row = page.locator('div.border-b').filter({ hasText: 'Replacement Customer' })
  await row.getByRole('button', { name: 'Cancel Weekly Reservation' }).click()
  await page.getByText('Reservation cancelled. The time is available again.').waitFor()
  assert((await slots(nextWeek)).some(slot => slot.start_time === '17:00'))
  const managed = await page.request.post(`${base}/api/reservations/manage`, { data: { token: replacement.management_token } })
  assert.equal((await managed.json()).status, 'cancelled')
  await page.goto(`${base}/admin/account`)
  await page.getByLabel('Current password', { exact: true }).fill('wrong-password')
  await page.getByLabel('New password', { exact: true }).fill('updated-weekly-password')
  await page.getByLabel('Confirm new password', { exact: true }).fill('updated-weekly-password')
  await page.getByRole('button', { name: 'Update Password', exact: true }).click()
  await page.getByRole('alert').filter({ hasText: 'Your current password is incorrect.' }).waitFor()
  await page.getByLabel('Current password', { exact: true }).fill('weekly-test-password')
  await page.getByRole('button', { name: 'Update Password', exact: true }).click()
  await page.getByText('Your password has been updated.', { exact: false }).waitFor()
  assert.equal(await page.getByLabel('Current password', { exact: true }).inputValue(), '')
  assert.equal(await page.getByLabel('New password', { exact: true }).inputValue(), '')
  await page.getByRole('button', { name: 'Logout', exact: true }).first().click()
  await page.waitForURL('**/admin/login')
  await page.getByPlaceholder('Enter email').fill('weekly-admin@example.com')
  await page.getByPlaceholder('Enter password').fill('updated-weekly-password')
  await page.getByRole('button', { name: 'Login', exact: true }).click()
  await page.waitForURL('**/admin/dashboard')
  console.log('PASS: admin changes password with current-password validation and can log in with the new password')
  assert.deepEqual(runtime, [])
  console.log('PASS: Rylie can cancel the standing reservation from admin and release the time')
  await page.getByRole('button', { name: 'Logout', exact: true }).first().click()
  await page.waitForURL('**/admin/login')
  await page.goto(`${base}/rider/sign-in`)
  await page.getByLabel('Email address', { exact: true }).fill('rider-portal@example.com')
  await page.getByRole('button', { name: 'Email Me a Sign-In Code', exact: true }).click()
  await page.getByText('Your sign-in code has been sent.', { exact: true }).waitFor()
  // The isolated test environment logs mail instead of sending to real people.
  const mailLog = readFileSync(new URL('../api/storage/logs/laravel.log', import.meta.url), 'utf8')
  const code = [...mailLog.matchAll(/Your sign-in code is: (\d{6})/g)].at(-1)?.[1]
  assert(code, 'test sign-in email contains a code')
  await page.getByLabel('Six-digit sign-in code', { exact: true }).fill(code)
  await page.getByRole('button', { name: 'Verify and Sign In', exact: true }).click()
  await page.waitForURL('**/rider')
  await page.getByLabel('First name', { exact: true }).fill('Portal')
  await page.getByLabel('Last name', { exact: true }).fill('Customer')
  await page.getByRole('button', { name: 'Save Contact Details', exact: true }).click()
  await page.getByText('Contact details saved.', { exact: true }).waitFor()

  async function saveRider(first, last, birthday) {
    await page.getByLabel('Rider first name', { exact: true }).fill(first)
    await page.getByLabel('Rider last name', { exact: true }).fill(last)
    await page.getByLabel('Date of birth', { exact: true }).fill(birthday)
    await page.getByRole('button', { name: 'Save Rider', exact: true }).click()
    await page.getByText('Rider saved. Matching existing profiles are reused.', { exact: true }).waitFor()
  }
  await saveRider('Emma', 'Smith', '2010-01-01')
  await saveRider(' emma ', 'SMITH', '2010-01-01')
  assert.equal(await page.getByText('Emma Smith', { exact: true }).count(), 1)
  await saveRider('Hailey', 'Smith', '2012-01-01')
  await page.getByRole('link', { name: 'Book a Weekly Lesson', exact: true }).click()
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.locator('input[type=date]').first().fill(nextWeek)
  await page.getByRole('button', { name: '4:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  assert.equal(await page.getByPlaceholder('john@example.com').inputValue(), 'rider-portal@example.com')
  await page.getByLabel('Choose a saved rider', { exact: true }).selectOption({ label: 'Emma Smith' })
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.getByRole('button', { name: 'Reserve Weekly Lesson', exact: true }).click()
  await page.getByText('Your Weekly Lesson Is Reserved!', { exact: true }).waitFor()
  await page.goto(`${base}/rider`)
  await page.getByRole('button', { name: 'Cancel Weekly Reservation', exact: true }).waitFor()
  assert.equal(await page.getByText('Emma Smith', { exact: true }).count(), 1)
  await page.getByRole('button', { name: 'Cancel Weekly Reservation', exact: true }).click()
  await page.getByText('Weekly reservation cancelled.', { exact: true }).waitFor()
  assert((await slots(nextWeek)).some(slot => slot.start_time === '16:00'))
  assert.deepEqual(runtime, [])
  console.log('PASS: passwordless rider portal reuses duplicate profiles, selects saved riders for booking, and manages owned reservations')
  console.log('Weekly browser test: all flows passed, no runtime errors')
} finally {
  await browser?.close()
  await vite?.close()
  if (php?.pid) {
    try { process.kill(-php.pid, 'SIGTERM') } catch {}
  }
}
