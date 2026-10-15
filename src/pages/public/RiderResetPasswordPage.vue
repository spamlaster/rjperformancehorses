<template>
  <PublicLayout>
    <section class="container-narrow py-12">
      <div class="card card-padding max-w-lg mx-auto space-y-5">
        <h1 class="text-3xl font-serif font-bold">Reset Password</h1>
        <template v-if="done">
          <p role="status">Your password has been reset.</p>
          <RouterLink :to="signInLink" class="btn-primary inline-block">Sign In</RouterLink>
        </template>
        <template v-else-if="!token || !email">
          <p role="alert">This reset link is incomplete. Request a new password reset email.</p>
          <RouterLink :to="signInLink" class="underline">Back to sign in</RouterLink>
        </template>
        <form v-else class="space-y-4" @submit.prevent="reset">
          <p class="text-light-brown">Choose a new password for {{ email }}. If you use Google, Microsoft, or Apple, change that provider’s password on their website.</p>
          <div>
            <label for="new-password" class="form-label">New password</label>
            <input id="new-password" v-model="password" type="password" autocomplete="new-password" minlength="8" maxlength="255" required class="form-input" />
          </div>
          <div>
            <label for="confirm-password" class="form-label">Confirm new password</label>
            <input id="confirm-password" v-model="confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="255" required class="form-input" />
          </div>
          <button class="btn-primary" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Reset Password' }}</button>
          <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
          <RouterLink v-if="error" :to="signInLink" class="underline block">Request another reset link</RouterLink>
        </form>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import { api } from '@/lib/api'
const route = useRoute()
const email = typeof route.query.email === 'string' ? route.query.email : ''
const token = typeof route.query.token === 'string' ? route.query.token : ''
const signInLink = computed(() => route.query.returnTo === '/book' ? '/rider/sign-in?returnTo=/book' : '/rider/sign-in')
const password = ref('')
const confirmation = ref('')
const busy = ref(false)
const done = ref(false)
const error = ref('')
async function reset() {
  busy.value = true
  error.value = ''
  try {
    const result = await api('POST', '/rider/auth/reset-password', { email, token, password: password.value, password_confirmation: confirmation.value })
    if (result.error) throw new Error(result.error)
    password.value = ''
    confirmation.value = ''
    done.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to reset your password.'
  } finally { busy.value = false }
}
</script>
