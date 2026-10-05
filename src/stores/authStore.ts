import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { supabase } from '@/lib/supabase'
import type { User } from '@supabase/supabase-js'

export interface AuthState {
  user: User | null
  isAdmin: boolean
  adminProfile: {
    id: string
    name: string | null
    email: string
    role: string
  } | null
}

/**
 * Authentication Store - Phase 2B: Supabase Auth
 *
 * Real authentication using Supabase Auth + admin_profiles table
 * Session management via Supabase client
 * RLS enforces authorization at database level
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAdmin = ref(false)
  const adminProfile = ref<AuthState['adminProfile']>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)

  /**
   * Initialize auth state from Supabase session
   * Called on app startup
   */
  let initialization: Promise<void> | undefined
  function initializeAuth(): Promise<void> {
    initialization ??= restoreAuth()
    return initialization
  }

  async function restoreAuth() {
    isLoading.value = true
    try {
      // Get current session
      const { data } = await supabase.auth.getSession()

      if (data.session?.user) {
        user.value = data.session.user
        // Check if user is admin
        await checkAdminStatus(data.session.user.id)
      }

      // Listen for auth changes
      supabase.auth.onAuthStateChange((_event, session) => {
        if (session?.user) {
          user.value = session.user
          // Run database requests after the auth callback releases its lock.
          const id = session.user.id
          setTimeout(() => {
            if (user.value?.id === id) void checkAdminStatus(id)
          }, 0)
        } else {
          user.value = null
          isAdmin.value = false
          adminProfile.value = null
        }
      })
    } catch (err) {
      console.error('Failed to initialize auth:', err)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Check if current user is an admin
   * Query admin_profiles table
   */
  async function checkAdminStatus(userId: string) {
    try {
      const { data, error: err } = await supabase
        .from('admin_profiles')
        .select('id, name, email, role')
        .eq('auth_user_id', userId)
        .eq('is_active', true)
        .single()

      if (err) {
        // User is not an admin
        isAdmin.value = false
        adminProfile.value = null
        return
      }

      isAdmin.value = true
      adminProfile.value = data
    } catch (err) {
      console.error('Error checking admin status:', err)
      isAdmin.value = false
      adminProfile.value = null
    }
  }

  /**
   * Login with email/password via Supabase Auth
   */
  async function login(email: string, password: string) {
    error.value = null
    isLoading.value = true

    try {
      const { data, error: err } = await supabase.auth.signInWithPassword({
        email,
        password,
      })

      if (err) {
        error.value = err.message
        return false
      }

      if (data.user) {
        user.value = data.user
        await checkAdminStatus(data.user.id)

        if (!isAdmin.value) {
          // User authenticated but not an admin
          error.value = 'Access denied. This account is not authorized as an administrator.'
          await logout()
          return false
        }

        return true
      }

      return false
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Login failed'
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Logout and clear auth state
   */
  async function logout() {
    isLoading.value = true
    try {
      await supabase.auth.signOut()
      user.value = null
      isAdmin.value = false
      adminProfile.value = null
      error.value = null
    } catch (err) {
      console.error('Logout failed:', err)
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    user,
    isAdmin,
    adminProfile,
    isLoading,
    error,

    // Computed
    isAuthenticated,

    // Methods
    initializeAuth,
    login,
    logout,
    checkAdminStatus,
  }
})
