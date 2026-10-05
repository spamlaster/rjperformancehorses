<template>
  <div v-if="!authReady" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <p class="text-gray-600">Initializing...</p>
    </div>
  </div>
  <RouterView v-else />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()
const authReady = ref(false)

// Initialize Supabase session on app startup
onMounted(async () => {
  try {
    await authStore.initializeAuth()
  } catch (err) {
    console.error('Failed to initialize auth:', err)
  } finally {
    authReady.value = true
  }
})
</script>
