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

// The service role key is needed to drop functions
const serviceRoleKey = env.VITE_SUPABASE_SERVICE_KEY
if (!serviceRoleKey) {
  console.log('⚠️  VITE_SUPABASE_SERVICE_KEY not found in .env.local')
  console.log('Cannot apply migration via client')
  console.log('\nManual fix required:')
  console.log('Run this SQL in Supabase dashboard → SQL Editor:\n')
  console.log(`DROP FUNCTION IF EXISTS public.create_guest_booking(text, timestamp, timestamp, text, text, text, text, jsonb);\n`)
  console.log(`CREATE OR REPLACE FUNCTION public.create_guest_booking(`)
  console.log(`  p_lesson_type_id text,`)
  console.log(`  p_start_time timestamp,`)
  console.log(`  p_end_time timestamp,`)
  console.log(`  p_customer_first_name text,`)
  console.log(`  p_customer_last_name text,`)
  console.log(`  p_customer_email text,`)
  console.log(`  p_customer_phone text,`)
  console.log(`  p_riders jsonb`)
  console.log(`) RETURNS jsonb`)
  console.log(`LANGUAGE plpgsql`)
  console.log(`SET search_path = pg_catalog, public`)
  console.log(`SECURITY DEFINER AS $$`)
  console.log(`...[see supabase/migrations/20241005000005_fix_create_guest_booking_deleted_at.sql]`)
  console.log(`...$$;`)
  
  process.exit(0)
}

const admin = createClient(env.VITE_SUPABASE_URL, serviceRoleKey)

console.log('Attempting to apply RPC fix...\n')

// Try calling the function - this won't fix it, but shows the current state
console.log('1. Testing current RPC...')
const testEmail = `test${Date.now()}@example.com`
const { data, error } = await admin.rpc('create_guest_booking', {
  p_lesson_type_id: 'lesson-1',
  p_start_time: new Date('2099-01-06T14:00:00').toISOString(),
  p_end_time: new Date('2099-01-06T14:30:00').toISOString(),
  p_customer_first_name: 'Test',
  p_customer_last_name: 'User',
  p_customer_email: testEmail,
  p_customer_phone: '555-1234',
  p_riders: [{ firstName: 'Test', lastName: 'User', dateOfBirth: '1990-05-20' }]
})

if (data?.error) {
  console.log(`   ❌ Still failing: ${data.error}`)
} else if (data?.success) {
  console.log(`   ✅ RPC fixed! Booking created: ${data.booking_id}`)
} else {
  console.log(`   ⚠️  Unexpected response:`, data)
}

console.log('\n2. To fix the database, you must:')
console.log('   - Restart Supabase: supabase stop && supabase start')
console.log('   OR')
console.log('   - Go to Supabase dashboard → SQL Editor')
console.log('   - Run the contents of:')
console.log('     supabase/migrations/20241005000005_fix_create_guest_booking_deleted_at.sql')
