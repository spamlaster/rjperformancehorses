import { chromium } from '@playwright/test'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

try {
  await page.goto('http://localhost:5174/book')

  // Step 1-2: Get to step 3
  await page.getByRole('button', { name: /Walk & Trot/ }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  
  await page.locator('input[type=date]').first().fill('2099-01-06')
  await page.waitForTimeout(400)
  await page.getByRole('button', { name: '2:00 PM', exact: true }).click()
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await page.waitForTimeout(300)

  console.log('=== FORM STATE AT STEP 3 ===\n')

  // Fill customer info
  const firstNameField = page.locator('input[placeholder="John"]')
  const lastNameField = page.locator('input[placeholder="Doe"]').first()
  const emailField = page.locator('input[placeholder="john@example.com"]')
  
  await firstNameField.fill('John')
  await lastNameField.fill('Doe')
  await emailField.fill('john@debug.com')
  
  console.log('Customer info filled')

  // Fill DOB
  const dobRequired = page.locator('input[type=date][required]')
  await dobRequired.fill('1990-05-20')
  console.log('DOB filled: 1990-05-20')

  await page.waitForTimeout(500)
  
  // Check Next button
  const nextBtn = page.getByRole('button', { name: 'Next', exact: true })
  const isEnabled = await nextBtn.isEnabled()
  
  console.log(`\nNext button enabled: ${isEnabled}`)
  
  if (!isEnabled) {
    // Check values again
    const fn = await firstNameField.inputValue()
    const ln = await lastNameField.inputValue()
    const em = await emailField.inputValue()
    const dob = await dobRequired.inputValue()
    
    console.log('\nForm values:')
    console.log(`  firstName: [${fn}]`)
    console.log(`  lastName: [${ln}]`)
    console.log(`  email: [${em}]`)
    console.log(`  dob: [${dob}]`)
    
    // Check HTML attributes
    const fnAttr = await firstNameField.getAttribute('value')
    const dobAttr = await dobRequired.getAttribute('value')
    console.log('\nHTML value attributes:')
    console.log(`  firstName: [${fnAttr}]`)
    console.log(`  dob: [${dobAttr}]`)
  }

} catch (e) {
  console.error('Error:', e.message)
} finally {
  await browser.close()
}
