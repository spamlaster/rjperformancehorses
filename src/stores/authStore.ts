import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface AdminUser {
  id: string
  email: string
  name?: string
  role: 'admin' | 'instructor' | 'staff'
}

/**
 * Authentication Store - PHASE 1 PLACEHOLDER
 *
 * This store will be replaced with Supabase Auth in Phase 2.
 *
 * IMPORTANT: No real authentication is implemented here.
 * The login page and admin pages are UI placeholders only.
 *
 * Phase 2 will:
 * - Remove this store entirely
 * - Implement Supabase Auth (@supabase/supabase-js)
 * - Use Supabase Session tokens instead of localStorage
 * - Implement proper password hashing and security
 *
 * DO NOT ADD REAL CREDENTIALS, PASSWORDS, OR AUTHENTICATION LOGIC HERE.
 */
export const useAuthStore = defineStore('auth', () => {
  const currentUser = ref<AdminUser | null>(null)
  const token = ref<string>('')
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!currentUser.value)

  /**
   * login - PLACEHOLDER ONLY
   *
   * Phase 1: Does not authenticate. Rejects all attempts.
   * Phase 2: Will call Supabase Auth signInWithPassword
   *
   * For development/testing purposes only, you can manually set:
   * const auth = useAuthStore()
   * auth.currentUser = { id: 'test', email: 'test@example.com', role: 'admin' }
   * auth.token = 'placeholder'
   */
  function login(email: string, password: string) {
    console.warn('⚠️  Authentication not implemented. Awaiting Supabase integration in Phase 2.')
    console.warn('⚠️  Admin login page is a UI placeholder only.')
    return false
  }

  function logout() {
    currentUser.value = null
    token.value = ''
  }

  function loadAuthFromStorage() {
    // Phase 1: Do not load from localStorage
    // Phase 2: Will load Supabase session automatically
  }

  /**
   * FOR DEVELOPMENT/TESTING ONLY
   * Temporarily set auth state for UI testing
   * Remove this before Phase 2 implementation
   */
  function setAuthForTesting(user: AdminUser, testToken: string) {
    console.warn('⚠️  Using test auth. This will be removed in Phase 2.')
    currentUser.value = user
    token.value = testToken
  }

  return {
    currentUser,
    token,
    isAuthenticated,
    isLoading,
    login,
    logout,
    loadAuthFromStorage,
    setAuthForTesting,
  }
})
