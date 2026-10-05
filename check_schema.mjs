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

const admin = createClient(env.VITE_SUPABASE_URL, env.VITE_SUPABASE_SERVICE_KEY || env.VITE_SUPABASE_ANON_KEY)

console.log('=== CHECKING TABLE SCHEMAS ===\n')

const tables = ['lesson_types', 'bookings', 'customers', 'riders', 'horses']

for (const table of tables) {
  const { data, error } = await admin
    .from('information_schema.columns')
    .select('column_name')
    .eq('table_name', table)
    .eq('table_schema', 'public')
  
  if (error) {
    console.log(`${table}: ERROR - ${error.message}`)
  } else {
    const hasDeleted = data?.some(c => c.column_name === 'deleted_at')
    const hasActive = data?.some(c => c.column_name === 'active')
    const columns = data?.map(c => c.column_name) || []
    
    console.log(`${table}:`)
    console.log(`  deleted_at: ${hasDeleted ? '✅ YES' : '❌ NO'}`)
    console.log(`  active: ${hasActive ? '✅ YES' : '❌ NO'}`)
    console.log(`  all columns: ${columns.slice(0, 5).join(', ')}...`)
    console.log()
  }
}
