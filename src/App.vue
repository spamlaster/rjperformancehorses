<template>
  <RouterView />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()

// Initialize Supabase session on app startup
onMounted(async () => {
  await authStore.initializeAuth()
})
</script>
