import { createClient } from '@supabase/supabase-js'

const supabase = createClient(
  'https://tgqhkrjnlnxwhtzlypkz.supabase.co',
  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRncWhrcmpubG54d2h0emx5cGt6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3MjgwNjUyNDUsImV4cCI6MjA0MzY0MTI0NX0.K79C-CxkL3V7Dq7K_nfDL0DQXmCQr_OUpWzfcR1ixkY'
)

async function testBooking() {
  console.log("=== Testing Booking Flow ===\n")

  // Step 1: Get business config
  console.log("1. Checking business timezone...")
  const { data: configData } = await supabase
    .from('business_config')
    .select('config_value')
    .eq('config_key', 'business_timezone')
    .single()
  console.log(`   Business timezone: ${configData?.config_value || 'N/A'}`)

  // Step 2: Get lesson types
  console.log("\n2. Getting lesson types...")
  const { data: lessons } = await supabase
    .from('lesson_types')
    .select('id, name, duration_minutes')
    .eq('active', true)
    .limit(1)

  if (!lessons?.[0]) {
    console.log("   ERROR: No active lesson types found")
    return
  }

  const lesson = lessons[0]
  console.log(`   Using lesson: ${lesson.name} (ID: ${lesson.id}, Duration: ${lesson.duration_minutes}min)`)

  // Step 3: Get available slots for a specific date
  console.log("\n3. Getting available slots for 2026-10-05...")
  const { data: slots, error: slotsError } = await supabase
    .rpc('get_bookable_slots', {
      p_lesson_type_id: lesson.id,
      p_date: '2026-10-05'
    })

  if (slotsError) {
    console.log(`   ERROR: ${slotsError.message}`)
    return
  }

  if (!slots || slots.length === 0) {
    console.log("   No slots available on 2026-10-05")
    return
  }

  const firstSlot = slots[0]
  console.log(`   Available slots:`)
  slots.slice(0, 3).forEach(s => {
    console.log(`     - ${s.start_time} to ${s.end_time}`)
  })

  // Step 4: Attempt booking for the first slot
  console.log(`\n4. Attempting to book ${firstSlot.start_time}...`)

  const selectedDate = '2026-10-05'
  const selectedTime = firstSlot.start_time

  // FIXED CODE - matching what BookingWizard now does
  const startTime = `${selectedDate}T${selectedTime}:00`

  const [startHours, startMinutes] = selectedTime.split(':').map(Number)
  const totalMinutes = startHours * 60 + startMinutes + lesson.duration_minutes
  const endHours = Math.floor(totalMinutes / 60) % 24
  const endMinutes = totalMinutes % 60

  const dayOffset = Math.floor((startHours * 60 + startMinutes + lesson.duration_minutes) / (24 * 60))
  const [year, month, day] = selectedDate.split('-').map(Number)
  const endDate = new Date(year, month - 1, day + dayOffset)
  const endDateStr = `${endDate.getFullYear()}-${String(endDate.getMonth() + 1).padStart(2, '0')}-${String(endDate.getDate()).padStart(2, '0')}`
  const endTime = `${endDateStr}T${String(endHours).padStart(2, '0')}:${String(endMinutes).padStart(2, '0')}:00`

  console.log(`   Sending to create_guest_booking:`)
  console.log(`     startTime: ${startTime}`)
  console.log(`     endTime: ${endTime}`)

  const { data: bookingResult, error: bookingError } = await supabase
    .rpc('create_guest_booking', {
      p_lesson_type_id: lesson.id,
      p_start_time: startTime,
      p_end_time: endTime,
      p_customer_first_name: 'Test',
      p_customer_last_name: 'Booking',
      p_customer_email: `test+${Date.now()}@example.com`,
      p_customer_phone: '555-1234',
      p_riders: [{
        firstName: 'Test',
        lastName: 'Rider',
        dateOfBirth: '2010-01-01'
      }]
    })

  if (bookingError) {
    console.log(`   ✗ FAILED: ${bookingError.message}`)
  } else if (bookingResult?.error) {
    console.log(`   ✗ FAILED: ${bookingResult.error}`)
  } else if (bookingResult?.success) {
    console.log(`   ✓ SUCCESS!`)
    console.log(`     Booking ID: ${bookingResult.booking_id}`)
    console.log(`     Customer ID: ${bookingResult.customer_id}`)
  } else {
    console.log(`   ✗ FAILED: Unexpected response ${JSON.stringify(bookingResult)}`)
  }
}

testBooking().catch(err => {
  console.error("Error:", err.message)
  process.exit(1)
})
