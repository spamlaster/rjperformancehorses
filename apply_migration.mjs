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

const supabase = createClient(env.VITE_SUPABASE_URL, env.VITE_SUPABASE_SERVICE_KEY || env.VITE_SUPABASE_ANON_KEY)

const migrationSQL = readFileSync('./supabase/migrations/20241005000005_fix_create_guest_booking_deleted_at.sql', 'utf8')

console.log('Applying migration...')
console.log(migrationSQL.slice(0, 200) + '...\n')

try {
  const { error } = await supabase.rpc('exec_sql', { sql: migrationSQL })
  if (error) {
    console.log('Error:', error)
  } else {
    console.log('✓ Migration applied!')
  }
} catch (e) {
  console.log('Exception:', e.message)
  console.log('Note: rpc exec_sql may not be available, trying direct execution...')
}
