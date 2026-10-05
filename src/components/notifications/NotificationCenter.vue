<template>
  <div
    v-if="notifications.length > 0"
    class="fixed top-4 right-4 z-50 space-y-3 pointer-events-none"
    role="region"
    aria-label="Notifications"
    aria-live="polite"
    aria-atomic="false"
  >
    <transition-group name="toast" tag="div">
      <div
        v-for="notif in notifications"
        :key="notif.id"
        class="pointer-events-auto"
      >
        <div
          :class="[
            'px-4 py-3 rounded-lg shadow-md flex items-center gap-3 max-w-sm',
            notif.type === 'success'
              ? 'bg-green-50 border border-green-200'
              : 'bg-red-50 border border-red-200'
          ]"
        >
          <div v-if="notif.type === 'success'" class="text-green-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div v-else class="text-red-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>

          <p :class="[
            'text-sm font-medium flex-1',
            notif.type === 'success' ? 'text-green-800' : 'text-red-800'
          ]">
            {{ notif.message }}
          </p>

          <button
            @click="dismiss(notif.id)"
            :class="[
              'flex-shrink-0 inline-flex text-sm hover:opacity-70 transition-opacity',
              notif.type === 'success' ? 'text-green-600' : 'text-red-600'
            ]"
            aria-label="Dismiss notification"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup lang="ts">
import { useNotification } from '@/composables/useNotification'

const { notifications, dismiss } = useNotification()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
