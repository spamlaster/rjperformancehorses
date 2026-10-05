<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6 animate-in">
      <h2 class="text-xl font-bold text-text-dark mb-2">{{ title }}</h2>
      <p class="text-light-brown mb-6">{{ message }}</p>

      <div class="flex gap-3">
        <button
          @click="handleCancel"
          class="btn-secondary flex-1"
        >
          {{ cancelText }}
        </button>
        <button
          @click="handleConfirm"
          :class="[
            'flex-1 px-4 py-2 rounded font-medium transition-colors',
            isDangerous
              ? 'bg-red-600 text-white hover:bg-red-700'
              : 'bg-accent-gold text-white hover:bg-warm-gold'
          ]"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog'

const { isOpen, title, message, confirmText, cancelText, isDangerous, handleConfirm, handleCancel } = useConfirmDialog()
</script>

<style scoped>
.animate-in {
  animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
