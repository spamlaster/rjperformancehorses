<template>
  <PublicLayout>
    <section class="container-narrow py-12">
      <div class="card card-padding max-w-lg mx-auto space-y-5">
        <h1 class="text-3xl font-serif font-bold">Rider Sign In</h1>
        <p class="text-light-brown">Sign in or create an account to save your riders and manage lessons. Parents can manage their children from one account.</p>
        <div class="grid gap-3">
          <a v-for="provider in enabledProviders" :key="provider.key" :href="`/api/rider/oauth/${provider.key}/redirect`" class="btn-secondary text-center">Continue with {{ provider.label }}</a>
        </div>
        <p v-if="pendingEmail" class="text-sm">Verify your email once to finish linking your sign-in.</p>
        <form @submit.prevent="codeSent ? verify() : sendCode()" class="space-y-4">
          <div>
            <label for="rider-email" class="form-label">Email address</label>
            <input id="rider-email" v-model="email" type="email" autocomplete="email" class="form-input" required :readonly="codeSent || !!pendingEmail" />
          </div>
          <div v-if="codeSent">
            <label for="rider-code" class="form-label">Six-digit sign-in code</label>
            <input id="rider-code" v-model="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" maxlength="6" class="form-input" required />
            <p class="text-sm text-light-brown mt-2">Check your inbox. The code expires in 10 minutes.</p>
          </div>
          <button class="btn-primary w-full" :disabled="busy || loading" type="submit">{{ busy ? 'Please wait…' : codeSent ? 'Verify and Sign In' : 'Email Me a Sign-In Code' }}</button>
          <button v-if="codeSent" class="text-sm underline" type="button" :disabled="busy" @click="sendCode">Send a new code</button>
          <button v-if="codeSent && !pendingEmail" class="text-sm underline ml-4" type="button" @click="codeSent = false; code = ''; error = ''">Use a different email</button>
        </form>
        <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
        <p v-if="notice" role="status" class="text-light-brown">{{ notice }}</p>
        <p class="text-sm text-light-brown">No password needed. New here? Your account is created after you verify your email.</p>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const route = useRoute()
const email = ref('')
const code = ref('')
const pendingEmail = ref('')
const codeSent = ref(false)
const busy = ref(false)
const loading = ref(true)
const error = ref('')
const notice = ref('')
const providers = ref<Record<string, boolean>>({})
const providerNames = [{ key: 'google', label: 'Google' }, { key: 'microsoft', label: 'Microsoft' }, { key: 'apple', label: 'Apple' }]
const enabledProviders = computed(() => providerNames.filter(p => providers.value[p.key]))
onMounted(async () => {
  try {
    const result = await api<{ providers: Record<string, boolean>; pending_email: string | null }>('GET', '/rider/auth/options')
    if (result.error) throw new Error(result.error)
    providers.value = result.data?.providers || {}
    pendingEmail.value = result.data?.pending_email || ''
    if (pendingEmail.value) email.value = pendingEmail.value
    if (route.query.error) error.value = 'Unable to complete that sign-in. Please try again or sign in with your email.'
  } catch {
    error.value = 'Unable to load sign-in options. Please refresh and try again.'
  } finally {
    loading.value = false
  }
})
async function sendCode() {
  busy.value = true
  error.value = ''
  notice.value = ''
  try {
    const result = await api('POST', '/rider/auth/email', { email: email.value })
    if (result.error) throw new Error(result.error)
    codeSent.value = true
    code.value = ''
    notice.value = 'Your sign-in code has been sent.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to send your sign-in code.'
  } finally {
    busy.value = false
  }
}
async function verify() {
  busy.value = true
  error.value = ''
  try {
    const result = await api('POST', '/rider/auth/verify', { email: email.value, code: code.value })
    if (result.error) throw new Error(result.error)
    await useAuthStore().checkAdminStatus()
    await router.push(route.query.returnTo === '/book' ? '/book' : '/rider')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to sign in.'
  } finally {
    busy.value = false
  }
}
</script>
