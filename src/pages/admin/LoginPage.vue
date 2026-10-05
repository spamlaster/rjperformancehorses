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
            placeholder="Enter email"
            required
            :disabled="isLoading"
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
            :disabled="isLoading"
          />
        </div>

        <button
          type="submit"
          class="w-full btn-primary py-3 mb-4"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>

        <div v-if="error" class="p-4 bg-red-100 text-red-700 rounded-md text-sm">
          <p class="font-semibold mb-1">Login Failed</p>
          <p>{{ error }}</p>
        </div>
      </form>

      <div class="mt-8 p-4 bg-blue-50 rounded-lg text-sm text-blue-800 border border-blue-200">
        <p class="font-bold mb-3">Phase 2B: Supabase Auth Active</p>
        <p class="text-xs mb-2">
          To create a development admin account after <code>npm run db:reset</code>, see setup instructions in the repository documentation.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')

const isLoading = computed(() => authStore.isLoading)
const error = computed(() => authStore.error)

// If already authenticated admin, redirect to dashboard
onMounted(() => {
  if (authStore.isAuthenticated && authStore.isAdmin) {
    router.replace('/admin/dashboard')
  }
})

const handleLogin = async () => {
  if (!email.value || !password.value) {
    return
  }

  const success = await authStore.login(email.value, password.value)

  if (success) {
    // Redirect to requested page or dashboard
    const redirect = (route.query.redirect as string) || '/admin/dashboard'
    router.push(redirect)
  }
  // Error message set in store
}
</script>
