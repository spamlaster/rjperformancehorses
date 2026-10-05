<template>
  <div class="min-h-screen flex items-center justify-center bg-cream">
    <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-lg">
      <div class="mb-8 p-4 bg-yellow-50 border-l-4 border-yellow-400">
        <p class="text-yellow-800 font-bold mb-2">🚧 Phase 1 Placeholder</p>
        <p class="text-sm text-yellow-700">
          Admin authentication is not yet implemented. This page is a UI placeholder awaiting Supabase Auth integration in Phase 2.
        </p>
      </div>

      <h1 class="text-3xl font-serif font-bold text-text-dark text-center mb-2">RJ Admin</h1>
      <p class="text-center text-light-brown mb-8">Lesson & Horse Management</p>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input
            v-model="email"
            type="email"
            class="form-input"
            placeholder="Enter email"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input
            v-model="password"
            type="password"
            class="form-input"
            placeholder="Enter password"
            required
          />
        </div>

        <button
          type="submit"
          class="w-full btn-primary py-3 mb-4"
          :disabled="loading"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>

        <div v-if="error" class="p-4 bg-red-100 text-red-700 rounded-md text-sm">
          {{ error }}
        </div>
      </form>

      <div class="mt-8 p-4 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-200">
        <p class="font-bold mb-3 text-gray-900">For Development Testing Only:</p>
        <p class="mb-2">To test the admin UI without authentication:</p>
        <ol class="list-decimal list-inside space-y-1 text-xs mb-3">
          <li>Open browser console (F12)</li>
          <li>Run: <code class="bg-white px-1 text-gray-900">useAuthStore().setAuthForTesting({id: 'test', email: 'test@example.com', role: 'admin'}, 'test-token')</code></li>
          <li>Refresh page or navigate to /admin/dashboard</li>
        </ol>
        <p class="text-xs italic text-gray-600">This testing method will be removed in Phase 2.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  const success = authStore.login(email.value, password.value)

  if (success) {
    router.push('/admin/dashboard')
  } else {
    error.value = 'Authentication not available in Phase 1. See instructions below for testing.'
  }

  loading.value = false
}
</script>
