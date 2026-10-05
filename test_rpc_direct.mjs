import { createClient } from '@supabase/supabase-js'
import { readFileSync } from 'fs'

const env = Object.fromEntries(
  readFileSync('.env.local', 'utf8')
    .split('\n')
    .filter(l => l.includes('='))
    .map(l => {
      const i = l.indexOf('=')
      return [l.slice(0, i), l.slice(i + 1).trim()]
    })
)

const anon = createClient(env.VITE_SUPABASE_URL, env.VITE_SUPABASE_ANON_KEY, {
  auth: { persistSession: false }
})

console.log('=== DIRECT RPC TEST ===\n')

// Test data - same as browser would send
const testEmail = `test${Date.now()}@example.com`
const startTime = new Date('2099-01-06T14:00:00').toISOString()
const endTime = new Date('2099-01-06T14:30:00').toISOString()

console.log('Calling create_guest_booking with:')
console.log(`  email: ${testEmail}`)
console.log(`  start: ${startTime}`)
console.log(`  end: ${endTime}`)
console.log(`  riders: [John Doe, DOB 1990-05-20]\n`)

const { data, error } = await anon.rpc('create_guest_booking', {
  p_lesson_type_id: 'lesson-1',
  p_start_time: startTime,
  p_end_time: endTime,
  p_customer_first_name: 'John',
  p_customer_last_name: 'Doe',
  p_customer_email: testEmail,
  p_customer_phone: '555-1234',
  p_riders: [
    {
      firstName: 'John',
      lastName: 'Doe',
      dateOfBirth: '1990-05-20'
    }
  ]
})

console.log('RPC Response:')
console.log('  error:', error ? JSON.stringify(error, null, 2) : 'null')
console.log('  data:', JSON.stringify(data, null, 2))

if (data?.error) {
  console.log('\n⚠️  RPC returned error:', data.error)
}

if (data?.success) {
  console.log('\n✅ RPC succeeded, booking_id:', data.booking_id)
}

// Check database for created records
console.log('\n=== CHECKING DATABASE ===\n')

// Check if customer was created
const { data: customers, error: custError } = await anon
  .from('customers')
  .select('*')
  .eq('email', testEmail)

console.log('Customers with this email:')
console.log(`  count: ${customers?.length || 0}`)
if (customers?.length > 0) {
  console.log('  ✓ Customer exists:', customers[0].id)
}

// Check if any bookings exist for this date
const { data: bookings, error: bookErr } = await anon
  .from('bookings')
  .select('*')
  .gte('start_time', '2099-01-06')
  .lt('start_time', '2099-01-07')

console.log('\nBookings on 2099-01-06:')
console.log(`  count: ${bookings?.length || 0}`)
if (bookings?.length > 0) {
  bookings.forEach(b => {
    console.log(`  - ${b.id}: ${b.start_time}`)
  })
}

// Check riders
const { data: riders, error: rideErr } = await anon
  .from('riders')
  .select('*')
  .eq('first_name', 'John')
  .eq('last_name', 'Doe')

console.log('\nRiders named John Doe:')
console.log(`  count: ${riders?.length || 0}`)

console.log('\n=== CONCLUSION ===')
if (data?.error) {
  console.log('❌ RPC FAILED - Database state:')
  console.log(`   - Customer created: ${customers?.length > 0 ? 'YES' : 'NO'}`)
  console.log(`   - Booking created: ${bookings?.length > 0 ? 'YES' : 'NO'}`)
  console.log(`   - Rider created: ${riders?.length > 0 ? 'YES' : 'NO'}`)
  console.log(`\n   Error was: ${data.error}`)
} else if (data?.success) {
  console.log('✅ RPC SUCCEEDED')
}
