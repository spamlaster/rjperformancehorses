<template>
  <div class="min-h-screen flex items-center justify-center bg-cream">
    <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-lg">
      <h1 class="text-3xl font-serif font-bold text-text-dark text-center mb-2">RJ Admin</h1>
      <p class="text-center text-light-brown mb-8">Lesson & Horse Management</p>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input
            v-model="email"
            type="email"
            class="form-input"
            placeholder="admin@rjhorses.com"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input
            v-model="password"
            type="password"
            class="form-input"
            placeholder="••••••••"
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

      <div class="mt-8 p-4 bg-blue-50 rounded-lg text-sm text-blue-800">
        <p class="font-bold mb-2">Demo Credentials:</p>
        <p>Email: <code class="bg-white px-2 py-1 rounded">admin@rjhorses.com</code></p>
        <p>Password: <code class="bg-white px-2 py-1 rounded">demo123</code></p>
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
    error.value = 'Invalid email or password. Use demo123 for the demo account.'
  }

  loading.value = false
}
</script>
