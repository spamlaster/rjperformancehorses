import { createClient } from '@supabase/supabase-js'

/**
 * Supabase Client
 *
 * Initialized with environment variables:
 * - VITE_SUPABASE_URL: The Supabase project URL
 * - VITE_SUPABASE_ANON_KEY: The anonymous/public key (safe for frontend)
 *
 * NOTE: Never expose SUPABASE_SERVICE_ROLE_KEY in frontend code.
 * Service-role keys must stay backend-only.
 */

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

if (!supabaseUrl || !supabaseAnonKey) {
  throw new Error(
    'Missing Supabase environment variables. ' +
    'Make sure VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY are set in .env.local'
  )
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey)

/**
 * Development-only: Test connection to Supabase
 *
 * This helper is for verifying the local Supabase environment is working.
 * Remove or guard behind a dev-only check in production.
 */
export async function testSupabaseConnection() {
  try {
    const { data, error } = await supabase
      .from('lesson_types')
      .select('id, name, price')
      .limit(1)

    if (error) {
      console.error('Supabase connection test failed:', error)
      return false
    }

    console.log('✓ Supabase connection test passed. Sample data:', data)
    return true
  } catch (err) {
    console.error('Supabase connection test error:', err)
    return false
  }
}
