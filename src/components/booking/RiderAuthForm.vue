<template>
  <div class="space-y-4">
    <p class="text-light-brown">{{ pendingEmail ? 'Verify your email once to link this sign-in to your rider account.' : mode === 'create' ? 'Create one account for yourself or your family. Verify your email once, then use your password to sign in.' : mode === 'forgot' ? 'Enter your account email to receive a password reset link for this website. Google, Microsoft, and Apple passwords are managed on their own websites.' : 'Sign in with your email and password.' }}</p>
    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label for="rider-email" class="form-label">Email address</label>
        <input id="rider-email" v-model="email" type="email" autocomplete="email" class="form-input" required :readonly="codeSent || !!pendingEmail" />
      </div>
      <template v-if="!codeSent && !pendingEmail && mode !== 'forgot'">
        <div>
          <label for="rider-password" class="form-label">Password</label>
          <input id="rider-password" v-model="password" type="password" :autocomplete="mode === 'create' ? 'new-password' : 'current-password'" class="form-input" required :minlength="mode === 'create' ? 8 : undefined" maxlength="255" />
          <p v-if="mode === 'create'" class="text-sm mt-1">Use at least 8 characters.</p>
        </div>
        <div v-if="mode === 'create'">
          <label for="rider-password-confirm" class="form-label">Confirm password</label>
          <input id="rider-password-confirm" v-model="confirmation" type="password" autocomplete="new-password" class="form-input" required minlength="8" maxlength="255" />
        </div>
      </template>
      <div v-if="codeSent">
        <label for="rider-code" class="form-label">Email verification code</label>
        <input id="rider-code" v-model="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" maxlength="6" class="form-input" required />
        <p class="text-sm mt-2">Check your inbox. This verifies account setup, and expires in 10 minutes.</p>
      </div>
      <button class="btn-primary w-full" :disabled="busy" type="submit">{{ busy ? 'Please wait…' : codeSent ? 'Verify Email and Continue' : mode === 'forgot' ? 'Email Reset Link' : mode === 'create' || pendingEmail ? 'Send Email Verification Code' : 'Sign In' }}</button>
      <button v-if="codeSent" class="underline text-sm" type="button" :disabled="busy" @click="resendCode">Send a new code</button>
    </form>
    <div v-if="!pendingEmail" class="flex flex-wrap gap-4 text-sm">
      <button v-if="mode !== 'sign-in'" class="underline" type="button" :disabled="busy" @click="switchMode('sign-in')">Back to sign in</button>
      <button v-if="mode === 'sign-in'" class="underline" type="button" :disabled="busy" @click="switchMode('create')">Create account</button>
      <button v-if="mode === 'sign-in'" class="underline" type="button" :disabled="busy" @click="switchMode('forgot')">Reset password</button>
    </div>
    <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
    <p v-if="notice" role="status" class="text-light-brown">{{ notice }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps<{ pendingEmail?: string; returnTo?: string }>()
const emit = defineEmits<{ (event: 'signed-in'): void }>()
const email = ref(props.pendingEmail || '')
const password = ref('')
const confirmation = ref('')
const mode = ref<'sign-in' | 'create' | 'forgot'>('sign-in')
const codeSent = ref(false)
const code = ref('')
const busy = ref(false)
const error = ref('')
const notice = ref('')

function switchMode(next: 'sign-in' | 'create' | 'forgot') {
  mode.value = next
  codeSent.value = false
  code.value = ''
  password.value = ''
  confirmation.value = ''
  error.value = ''
  notice.value = ''
}
async function sendCode() {
  const result = await api('POST', '/rider/auth/email', {
    email: email.value, password: password.value, password_confirmation: confirmation.value,
  })
  if (result.error) throw new Error(result.error)
  codeSent.value = true
  code.value = ''
  notice.value = 'Your email verification code has been sent.'
}
async function resendCode() {
  busy.value = true
  error.value = ''
  notice.value = ''
  try { await sendCode() } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to send verification email.'
  } finally { busy.value = false }
}
async function submit() {
  busy.value = true
  error.value = ''
  notice.value = ''
  try {
    if (mode.value === 'forgot') {
      const result = await api<{ message: string }>('POST', '/rider/auth/forgot-password', { email: email.value, returnTo: props.returnTo })
      if (result.error) throw new Error(result.error)
      notice.value = result.data?.message || 'Check your email for a password reset link.'
      return
    }
    if (!codeSent.value && (mode.value === 'create' || props.pendingEmail)) {
      await sendCode()
      return
    }
    const result = codeSent.value
      ? await api('POST', '/rider/auth/verify', { email: email.value, code: code.value })
      : await api('POST', '/rider/auth/login', { email: email.value, password: password.value })
    if (result.error) throw new Error(result.error)
    await useAuthStore().checkAdminStatus()
    if (!useAuthStore().isAuthenticated) throw new Error('Your sign-in session could not be restored. Please refresh and sign in again.')
    password.value = ''
    confirmation.value = ''
    emit('signed-in')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to sign in.'
  } finally {
    busy.value = false
  }
}
</script>
