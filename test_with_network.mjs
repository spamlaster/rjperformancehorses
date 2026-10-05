import { chromium } from '@playwright/test'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()
let rpcResponse = null
let rpcError = null

page.on('response', async (response) => {
  if (response.url().includes('rpc/create_guest_booking')) {
    try {
      rpcResponse = await response.json()
      console.log('\n=== RPC RESPONSE RECEIVED ===')
      console.log('Status:', response.status())
      console.log('Body:', JSON.stringify(rpcResponse, null, 2))
    } catch (e) {
      const text = await response.text()
      console.log('Response parse error:', e.message)
      console.log('Raw response:', text.slice(0, 200))
    }
  }
})

page.on('requestfailed', (request) => {
  if (request.url().includes('rpc/create_guest_booking')) {
    rpcError = request.failure().errorText
    console.log('\n=== RPC REQUEST FAILED ===')
    console.log('Error:', rpcError)
  }
})

try {
  await page.goto('http://localhost:5174/book')

  // Go through steps
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  
  await page.locator('input[type=date]').first().fill('2099-01-06')
  await page.waitForTimeout(400)
  await page.getByRole('button', { name: '2:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()

  await page.locator('input[placeholder="John"]').fill('John')
  await page.locator('input[placeholder="Doe"]').first().fill('Doe')
  await page.locator('input[placeholder="john@example.com"]').fill(`test${Date.now()}@example.com`)
  
  await page.locator('input[type=checkbox]').click()
  await page.locator('input[type=date][required]').fill('1990-05-20')
  
  await page.waitForTimeout(200)
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.getByRole('heading', { name: 'Review Your Booking' }).waitFor()

  console.log('At review page, clicking "Book Lesson"...')
  await page.getByRole('button', { name: 'Book Lesson' }).click()

  // Wait for RPC response
  console.log('Waiting for RPC...')
  for (let i = 0; i < 50; i++) {
    if (rpcResponse || rpcError) break
    await page.waitForTimeout(100)
  }

  if (rpcResponse) {
    console.log('\n✓ RPC returned successfully')
    if (rpcResponse.success) {
      console.log('✓ Success: true, booking_id:', rpcResponse.booking_id)
    } else {
      console.log('✗ Success: false, error:', rpcResponse.error)
    }
  } else if (rpcError) {
    console.log('\n✗ RPC failed:', rpcError)
  } else {
    console.log('\n✗ No RPC response or error after 5 seconds')
  }

  // Check page state
  await page.waitForTimeout(1000)
  const confirmText = await page.getByText('Your Lesson Has Been Booked').count()
  const errorText = await page.getByRole('alert').count()
  const processingText = await page.getByText('Completing Your Booking').count()
  
  console.log('\nPage state:')
  console.log(`  - "Booked!" visible: ${confirmText > 0}`)
  console.log(`  - Error visible: ${errorText > 0}`)
  console.log(`  - "Processing..." visible: ${processingText > 0}`)

} finally {
  await browser.close()
}
