<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-border-light hidden md:flex flex-col">
      <!-- Logo -->
      <div class="p-6 border-b border-border-light">
        <router-link to="/admin/dashboard" class="font-serif text-lg font-bold text-text-dark">
          RJ Admin
        </router-link>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-6 space-y-2">
        <router-link
          to="/admin/dashboard"
          class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/dashboard') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'"
        >
          Dashboard
        </router-link>
        <router-link
          to="/admin/bookings"
          class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/bookings') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'"
        >
          Bookings
        </router-link>
        <router-link
          to="/admin/riders"
          class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/riders') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'"
        >
          Riders
        </router-link>
        <router-link
          to="/admin/horses"
          class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/horses') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'"
        >
          Horses
        </router-link>
        <router-link
          to="/admin/availability"
          class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/availability') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'"
        >
          Availability
        </router-link>
        <router-link to="/admin/account" class="block px-4 py-2 rounded-md transition-colors"
          :class="isActive('/admin/account') ? 'bg-accent-gold text-white' : 'text-text-dark hover:bg-gray-100'">
          Account Settings
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="p-6 border-t border-border-light">
        <button
          @click="logout"
          class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors text-sm font-medium"
        >
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <!-- Mobile Header -->
      <div class="md:hidden flex items-center justify-between px-4 py-4 bg-white border-b border-border-light">
        <router-link to="/admin/dashboard" class="font-serif text-lg font-bold text-text-dark">
          RJ Admin
        </router-link>
        <button
          @click="logout"
          class="text-sm text-red-600 hover:text-red-700"
        >
          Logout
        </button>
      </div>

      <div class="md:hidden px-4 py-2 bg-white border-b border-border-light">
        <router-link to="/admin/account" class="text-sm font-semibold text-dark-brown underline">Account Settings</router-link>
        <router-link v-if="route.path === '/admin/account'" to="/admin/dashboard" class="text-sm ml-4 underline">Back to Dashboard</router-link>
      </div>

      <!-- Content -->
      <main class="flex-1 p-4 md:p-8 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useAdminStore } from '@/stores/adminStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const adminStore = useAdminStore()

const dataLoaded = ref(false)

// Load admin data once when entering admin area
onMounted(async () => {
  // Only load if not already loaded (singleton pattern)
  if (!dataLoaded.value && adminStore.customers.length === 0 && adminStore.riders.length === 0) {
    await adminStore.loadAllData()
  }
  dataLoaded.value = true
})

const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const logout = async () => {
  await authStore.logout()
  router.push('/admin/login')
}
</script>
