import { chromium } from '@playwright/test'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

try {
  console.log('Starting full booking test...\n')
  
  await page.goto('http://localhost:5174/book')
  console.log('✓ Page loaded')

  // Step 1: Select lesson
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  console.log('✓ Step 1: Lesson selected')

  // Step 2: Date & Time
  await page.locator('input[type=date]').first().fill('2099-01-06')
  await page.waitForTimeout(400)
  await page.getByRole('button', { name: '2:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  console.log('✓ Step 2: Date and time selected')

  // Step 3: Contact & Rider Info
  await page.locator('input[placeholder="John"]').fill('John')
  await page.locator('input[placeholder="Doe"]').first().fill('Doe')
  await page.locator('input[placeholder="john@example.com"]').fill(`test${Date.now()}@example.com`)
  console.log('✓ Filled customer info')

  // Check "I am the rider"
  const iamRiderCheckbox = page.locator('input[type=checkbox]')
  await iamRiderCheckbox.click()
  console.log('✓ Checked "I am the rider"')

  // Fill DOB
  const dobInput = page.locator('input[type=date][required]')
  await dobInput.fill('1990-05-20')
  console.log('✓ Filled DOB')

  await page.waitForTimeout(200)
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  console.log('✓ Step 3: Moved to review')

  // Step 4: Review - should see "Book Lesson" button
  await page.getByRole('heading', { name: 'Review Your Booking' }).waitFor()
  console.log('✓ Step 4: Review page displayed')

  // Find Book Lesson button
  const bookBtn = page.getByRole('button', { name: 'Book Lesson' })
  const bookBtnCount = await bookBtn.count()
  if (bookBtnCount === 0) {
    console.log('✗ "Book Lesson" button not found')
    process.exit(1)
  }
  console.log('✓ "Book Lesson" button found')

  // Click submit
  console.log('✓ Clicking "Book Lesson"...')
  await bookBtn.click()

  // Wait for confirmation (max 10 seconds)
  console.log('  Waiting for RPC response...')
  let success = false
  for (let i = 0; i < 100; i++) {
    const confirmText = await page.getByText('Your Lesson Has Been Booked').count()
    const errorText = await page.getByRole('alert').count()
    
    if (confirmText > 0) {
      const bookingIdText = await page.getByText(/Booking ID:/).textContent()
      console.log(`✓ SUCCESS! Confirmation appeared!`)
      console.log(`  ${bookingIdText}`)
      success = true
      break
    }
    if (errorText > 0) {
      const errorMsg = await page.getByRole('alert').first().textContent()
      console.log(`✗ ERROR: ${errorMsg}`)
      break
    }
    
    await page.waitForTimeout(100)
  }

  if (!success) {
    console.log('✗ Timeout - no confirmation or error appeared')
    process.exit(1)
  }

  console.log('\n✅ FULL BOOKING TEST PASSED')

} catch (e) {
  console.error('✗ Test error:', e.message)
  process.exit(1)
} finally {
  await browser.close()
}
