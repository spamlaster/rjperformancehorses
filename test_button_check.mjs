import { chromium } from '@playwright/test'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

try {
  await page.goto('http://localhost:5174/book')

  // Get to review page
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.locator('input[type=date]').first().fill('2099-01-06')
  await page.waitForTimeout(400)
  await page.getByRole('button', { name: '2:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()

  await page.locator('input[placeholder="John"]').fill('John')
  await page.locator('input[placeholder="Doe"]').first().fill('Doe')
  await page.locator('input[placeholder="john@example.com"]').fill('test@example.com')
  
  const iamRiderCheckbox = page.locator('input[type=checkbox]')
  await iamRiderCheckbox.click()
  
  const dobInput = page.locator('input[type=date][required]')
  await dobInput.fill('1990-05-20')
  
  await page.waitForTimeout(300)
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.getByRole('heading', { name: 'Review Your Booking' }).waitFor()

  // Now check what buttons are visible
  const allButtons = await page.getByRole('button').all()
  console.log('Buttons on review page:')
  for (const btn of allButtons) {
    const text = await btn.textContent()
    console.log(`  - [${text?.trim()}]`)
  }

  // Try different selectors
  console.log('\nTrying different selectors:')
  console.log('book-lesson (exact):', await page.getByRole('button', { name: 'Book Lesson' }).count())
  console.log('book-lesson (partial):', await page.getByRole('button').filter({ hasText: 'Book' }).count())
  console.log('book-lesson (case-insensitive):', await page.getByRole('button', { name: /book/i }).count())

} finally {
  await browser.close()
}
