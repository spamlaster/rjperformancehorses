import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/lib/api'

export interface AuthUser {
  id: string
  email: string
  name: string | null
}

export interface AuthState {
  user: AuthUser | null
  isAdmin: boolean
  adminProfile: {
    id: string
    name: string | null
    email: string
    role: string
  } | null
}

interface ProfileResponse {
  user: AuthUser
  is_admin: boolean
  admin_profile: AuthState['adminProfile']
}

/**
 * Authentication Store - Laravel Sanctum
 *
 * Admins sign in with a session cookie (Sanctum SPA authentication).
 * The API enforces authorization on every admin route; this store only
 * mirrors it for routing and display.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const isAdmin = ref(false)
  const adminProfile = ref<AuthState['adminProfile']>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)

  function setProfile(profile: ProfileResponse | null) {
    user.value = profile?.user ?? null
    isAdmin.value = profile?.is_admin ?? false
    adminProfile.value = profile?.admin_profile ?? null
  }

  /**
   * Restore auth state from the existing session cookie
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
      await checkAdminStatus()
    } catch (err) {
      console.error('Failed to initialize auth:', err)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Load the signed-in user and admin status (401 = signed out)
   */
  async function checkAdminStatus() {
    const session = await api<{ signed_in: boolean }>('GET', '/rider/auth/options')
    if (session.error) throw new Error(session.error)
    if (!session.data?.signed_in) {
      setProfile(null)
      return
    }
    const { data } = await api<ProfileResponse>('GET', '/auth/me')
    setProfile(data)
  }

  /**
   * Login with email/password
   */
  async function login(email: string, password: string) {
    error.value = null
    isLoading.value = true

    try {
      const { data, error: err } = await api<ProfileResponse>('POST', '/auth/login', { email, password })

      if (err || !data) {
        // Includes "Access denied…" for accounts that are not administrators.
        error.value = err || 'Login failed'
        setProfile(null)
        return false
      }

      setProfile(data)
      return isAdmin.value
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
      await api('POST', '/auth/logout')
      setProfile(null)
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
