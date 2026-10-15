import { chromium } from '@playwright/test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { base, sql, setupRiderAccount, signInRider } from './support.mjs'

const email = `auth-browser-${Date.now()}@example.com`
const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()
const runtime = []
page.on('pageerror', error => runtime.push(error.message))
try {
  await page.goto(`${base}/book`)
  await page.waitForURL('**/rider/sign-in?returnTo=/book')
  assert.equal(await page.getByRole('button', { name: 'Create account / Set password', exact: true }).count(), 0)
  await setupRiderAccount(page, email)
  await page.waitForURL('**/book')
  await page.getByRole('button', { name: /Walk & Trot/ }).waitFor()
  console.log('PASS: first-time account creation returns directly to booking before any lesson is selected')
  await page.goto(`${base}/rider`)
  await page.getByRole('button', { name: 'Sign Out', exact: true }).click()
  await page.waitForURL('**/rider/sign-in')
  await page.goto(`${base}/book`)
  await page.waitForURL('**/rider/sign-in?returnTo=/book')
  await page.getByRole('button', { name: 'Reset password', exact: true }).click()
  await page.getByLabel('Email address', { exact: true }).fill(email)
  assert.equal(await page.getByLabel('Password', { exact: true }).count(), 0)
  const logOffset = readFileSync(new URL('../api/storage/logs/laravel.log', import.meta.url), 'utf8').length
  await page.getByRole('button', { name: 'Email Reset Link', exact: true }).click()
  await page.getByRole('status').filter({ hasText: 'If a rider account exists' }).waitFor()
  // Mail is logged only in this isolated test environment; decode MIME line wrapping.
  const log = readFileSync(new URL('../api/storage/logs/laravel.log', import.meta.url), 'utf8').slice(logOffset)
    .replace(/=\r?\n/g, '').replace(/=3D/g, '=').replace(/&amp;/g, '&')
  const link = log.match(/https?:\/\/[^\s<>"\]]+\/rider\/reset-password\?[^\s<>"\]]+/)?.[0]
  assert(link, 'password reset mail contains a clickable website link')
  const resetUrl = new URL(link)
  const token = resetUrl.searchParams.get('token')
  assert(token)
  await page.goto(link)
  await page.getByRole('heading', { name: 'Reset Password', exact: true }).waitFor()
  await page.getByLabel('New password', { exact: true }).fill('Updated-browser-password!123')
  await page.getByLabel('Confirm new password', { exact: true }).fill('mismatched-password')
  await page.getByRole('button', { name: 'Reset Password', exact: true }).click()
  await page.getByRole('alert').waitFor()
  await page.getByLabel('Confirm new password', { exact: true }).fill('Updated-browser-password!123')
  await page.getByRole('button', { name: 'Reset Password', exact: true }).click()
  await page.getByRole('status').filter({ hasText: 'Your password has been reset.' }).waitFor()
  await page.getByRole('link', { name: 'Sign In', exact: true }).click()
  await page.waitForURL('**/rider/sign-in?returnTo=/book')
  await signInRider(page, email, 'Updated-browser-password!123')
  await page.waitForURL('**/book')
  await page.getByRole('button', { name: /Walk & Trot/ }).waitFor()
  console.log('PASS: emailed password-reset link opens the reset form and the new password returns the user to booking')
  assert.equal(sql(`SELECT COUNT(*) FROM users WHERE email='${email}'`).trim(), '1')
  assert.equal(sql(`SELECT COUNT(*) FROM customers WHERE email='${email}'`).trim(), '1')
  assert.deepEqual(runtime, [])
  console.log('Auth browser: all flows passed, no runtime errors')
} finally {
  await browser.close()
  sql(`DELETE FROM password_reset_tokens WHERE email='${email}'; DELETE FROM customers WHERE email='${email}'; DELETE FROM users WHERE email='${email}'`)
}
