<template>
  <AdminLayout>
    <div class="max-w-xl space-y-6">
      <div>
        <h1 class="text-3xl font-serif font-bold text-text-dark">Account Settings</h1>
        <p class="text-light-brown mt-2">{{ authStore.user?.email }}</p>
      </div>
      <form class="card card-padding space-y-5" @submit.prevent="updatePassword">
        <h2 class="text-xl font-serif font-bold">Update Password</h2>
        <div>
          <label for="current-password" class="form-label">Current password</label>
          <input id="current-password" v-model="currentPassword" type="password" autocomplete="current-password" required class="form-input" :disabled="saving" />
        </div>
        <div>
          <label for="new-password" class="form-label">New password</label>
          <input id="new-password" v-model="newPassword" type="password" autocomplete="new-password" required minlength="12" maxlength="72" class="form-input" aria-describedby="password-help" :disabled="saving" />
          <p id="password-help" class="text-sm text-light-brown mt-2">Use 12–72 characters and choose a password different from your current one.</p>
        </div>
        <div>
          <label for="confirm-password" class="form-label">Confirm new password</label>
          <input id="confirm-password" v-model="confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="72" class="form-input" :disabled="saving" />
        </div>
        <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
        <p v-if="success" role="status" class="text-green-700">{{ success }}</p>
        <button class="btn-primary" type="submit" :disabled="saving">{{ saving ? 'Updating…' : 'Update Password' }}</button>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAuthStore } from '@/stores/authStore'
import { api } from '@/lib/api'

const authStore = useAuthStore()
const currentPassword = ref('')
const newPassword = ref('')
const confirmation = ref('')
const saving = ref(false)
const error = ref('')
const success = ref('')

async function updatePassword() {
  error.value = ''
  success.value = ''
  if (newPassword.value !== confirmation.value) {
    error.value = 'The new passwords do not match.'
    return
  }
  saving.value = true
  try {
    const result = await api<{ message: string }>('PATCH', '/admin/password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmation.value,
    })
    if (result.error || !result.data) {
      error.value = result.error || 'Unable to update your password.'
      return
    }
    currentPassword.value = ''
    newPassword.value = ''
    confirmation.value = ''
    success.value = 'Your password has been updated. Use your new password the next time you log in.'
  } catch {
    error.value = 'Unable to update your password. Please try again.'
  } finally {
    saving.value = false
  }
}
</script>
