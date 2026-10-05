import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { AdminUser } from '@/types/models'

export const useAuthStore = defineStore('auth', () => {
  const currentUser = ref<AdminUser | null>(null)
  const token = ref<string>(localStorage.getItem('authToken') || '')

  const isAuthenticated = computed(() => !!token.value && !!currentUser.value)

  // Demo user for Phase 1
  const demoUser: AdminUser = {
    id: 'admin-1',
    email: 'admin@rjhorses.com',
    name: 'Rylie Jensen',
    role: 'admin',
    active: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  }

  function login(email: string, password: string) {
    // Phase 1: Simple demo login
    if (email === 'admin@rjhorses.com' && password === 'demo123') {
      const demoToken = `token-${Date.now()}`
      token.value = demoToken
      currentUser.value = demoUser
      localStorage.setItem('authToken', demoToken)
      localStorage.setItem('currentUser', JSON.stringify(demoUser))
      return true
    }
    return false
  }

  function logout() {
    token.value = ''
    currentUser.value = null
    localStorage.removeItem('authToken')
    localStorage.removeItem('currentUser')
  }

  function loadAuthFromStorage() {
    const storedToken = localStorage.getItem('authToken')
    const storedUser = localStorage.getItem('currentUser')

    if (storedToken && storedUser) {
      token.value = storedToken
      try {
        currentUser.value = JSON.parse(storedUser)
      } catch (e) {
        logout()
      }
    }
  }

  return {
    currentUser,
    token,
    isAuthenticated,
    login,
    logout,
    loadAuthFromStorage,
  }
})
