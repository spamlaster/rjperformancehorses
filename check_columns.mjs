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

console.log('=== CHECKING TABLE COLUMNS ===\n')

// Try to select from each table - if column doesn't exist, we'll get an error
const queries = [
  {
    table: 'lesson_types',
    query: anon.from('lesson_types').select('id, active, deleted_at').limit(1)
  },
  {
    table: 'bookings',
    query: anon.from('bookings').select('id, deleted_at').limit(1)
  },
  {
    table: 'customers',
    query: anon.from('customers').select('id, deleted_at').limit(1)
  },
  {
    table: 'riders',
    query: anon.from('riders').select('id, deleted_at').limit(1)
  },
  {
    table: 'horses',
    query: anon.from('horses').select('id, deleted_at').limit(1)
  }
]

for (const { table, query } of queries) {
  const { data, error } = await query
  
  if (error?.message?.includes('column') && error?.message?.includes('does not exist')) {
    console.log(`❌ ${table}: MISSING COLUMN`)
    console.log(`   Error: ${error.message}`)
  } else if (error) {
    console.log(`⚠️  ${table}: Error - ${error.message}`)
  } else {
    console.log(`✅ ${table}: Has deleted_at column`)
  }
}
