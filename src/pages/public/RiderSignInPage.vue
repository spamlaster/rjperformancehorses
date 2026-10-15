<template>
  <PublicLayout>
    <section class="container-narrow py-12 space-y-6">
      <div class="card card-padding max-w-lg mx-auto space-y-5">
        <h1 class="text-3xl font-serif font-bold">Rider Sign In</h1>
        <p class="text-light-brown">Sign in or create an account to book a lesson. Parents use their own account and add each child as a rider—children do not need a separate login.</p>
        <p v-if="returnTo === '/book'" role="status" class="font-semibold">Sign in first. We’ll return you to booking afterward.</p>
        <div class="grid gap-3">
          <a v-for="provider in enabledProviders" :key="provider.key" :href="`/api/rider/oauth/${provider.key}/redirect?returnTo=${encodeURIComponent(returnTo)}`" class="btn-secondary text-center">Continue with {{ provider.label }}</a>
        </div>
        <p v-if="enabledProviders.length" class="text-sm text-center text-light-brown">Or use your email</p>
        <p v-if="pendingEmail" class="text-sm">Verify your email once to finish linking your sign-in.</p>
        <RiderAuthForm v-if="!loading" :pending-email="pendingEmail" :return-to="returnTo" @signed-in="finishSignIn" />
        <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
      </div>
      <BookingManagement />
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BookingManagement from '@/components/booking/BookingManagement.vue'
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import { api } from '@/lib/api'
import RiderAuthForm from '@/components/booking/RiderAuthForm.vue'

const router = useRouter()
const route = useRoute()
const pendingEmail = ref('')
const ssoReturnTo = ref('/rider')
const returnTo = computed(() => route.query.returnTo === '/book' || ssoReturnTo.value === '/book' ? '/book' : '/rider')
const loading = ref(true)
const error = ref('')
const providers = ref<Record<string, boolean>>({})
const providerNames = [{ key: 'google', label: 'Google' }, { key: 'microsoft', label: 'Microsoft' }, { key: 'apple', label: 'Apple' }]
const enabledProviders = computed(() => providerNames.filter(p => providers.value[p.key]))
onMounted(async () => {
  try {
    const result = await api<{ providers: Record<string, boolean>; pending_email: string | null; return_to: string }>('GET', '/rider/auth/options')
    if (result.error) throw new Error(result.error)
    providers.value = result.data?.providers || {}
    pendingEmail.value = result.data?.pending_email || ''
    ssoReturnTo.value = pendingEmail.value ? result.data?.return_to || '/rider' : '/rider'
    if (route.query.error) error.value = 'Unable to complete that sign-in. Please try again or sign in with your email.'
  } catch {
    error.value = 'Unable to load sign-in options. Please refresh and try again.'
  } finally {
    loading.value = false
  }
})
async function finishSignIn() {
  await router.push(returnTo.value)
}
</script>
