import { chromium } from '@playwright/test'

const browser = await chromium.launch({ headless: false })
const page = await browser.newPage()

// Intercept RPC requests and log them
page.on('response', async (response) => {
  if (response.url().includes('rpc/create_guest_booking')) {
    const status = response.status()
    let body
    try {
      body = await response.json()
    } catch {
      body = await response.text()
    }
    console.log('\n=== RPC RESPONSE ===')
    console.log('Status:', status)
    console.log('Body:', JSON.stringify(body, null, 2))
  }
})

try {
  await page.goto('http://localhost:5176/book')

  // Step 1: Select lesson
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()

  // Step 2: Date & Time
  await page.locator('input[type=date]').first().fill('2099-01-06')
  await page.waitForTimeout(800)
  await page.getByRole('button', { name: '2:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()

  // Step 3: Contact & Rider Info
  await page.locator('input[placeholder="John"]').fill('John')
  await page.locator('input[placeholder="Doe"]').first().fill('Doe')
  await page.locator('input[placeholder="john@example.com"]').fill('john@debug.com')
  
  // Fill DOB
  const dobInput = page.locator('input[type=date][required]')
  await dobInput.fill('1990-05-20')
  
  // Move to review
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.waitForTimeout(500)
  console.log('✓ At review step')

  // Check what buttons are visible
  const buttons = await page.getByRole('button').all()
  console.log('\nVisible buttons:')
  for (const btn of buttons) {
    const text = await btn.textContent()
    const visible = await btn.isVisible()
    if (visible) console.log('  -', text?.trim())
  }

  // Try to find and click the submit button
  const submitBtn = page.getByRole('button').filter({ hasText: 'Book Lesson' })
  const count = await submitBtn.count()
  console.log('\n"Book Lesson" buttons found:', count)

  if (count > 0) {
    console.log('Clicking submit button...')
    await submitBtn.click()
    console.log('Waiting 4 seconds for RPC response...')
    await page.waitForTimeout(4000)
  }

} catch (e) {
  console.error('Error:', e.message)
} finally {
  await browser.close()
}
