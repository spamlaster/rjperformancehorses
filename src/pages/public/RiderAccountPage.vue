<template>
  <PublicLayout>
    <section class="container-narrow py-12 space-y-6">
      <div class="flex flex-wrap justify-between gap-3 items-center">
        <h1 class="text-3xl font-serif font-bold">Your Rider Account</h1>
        <button v-if="account" class="btn-secondary" @click="logout">Sign Out</button>
      </div>
      <p v-if="loading" role="status">Loading your account…</p>
      <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
      <p v-if="notice" role="status">{{ notice }}</p>
      <template v-if="account">
        <form class="card card-padding space-y-4" @submit.prevent="saveContact">
          <h2 class="text-xl font-serif font-bold">Your Contact Details</h2>
          <p class="text-sm text-light-brown">{{ account.customer.email }}</p>
          <div class="grid md:grid-cols-3 gap-4">
            <div><label for="contact-first" class="form-label">First name</label><input id="contact-first" v-model="contact.first_name" class="form-input" required /></div>
            <div><label for="contact-last" class="form-label">Last name</label><input id="contact-last" v-model="contact.last_name" class="form-input" required /></div>
            <div><label for="contact-phone" class="form-label">Phone</label><input id="contact-phone" v-model="contact.phone" class="form-input" type="tel" /></div>
          </div>
          <button class="btn-primary" :disabled="busy">Save Contact Details</button>
        </form>
        <div class="card card-padding space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-serif font-bold">Saved Riders</h2>
            <router-link to="/book" class="btn-primary">Book a Weekly Lesson</router-link>
          </div>
          <p class="text-light-brown">Add yourself or a child below. Each child is saved under your account and does not need an email address or a separate login. Choose their saved profile whenever you book.</p>
          <ul class="space-y-2">
            <li v-for="rider in account.riders" :key="rider.id" class="border border-border-light rounded p-3">
              <p class="font-bold">{{ rider.first_name }} {{ rider.last_name }}</p>
              <p class="text-sm text-light-brown">Date of birth: {{ rider.date_of_birth?.slice(0, 10) || 'Not provided' }}</p>
            </li>
          </ul>
          <form @submit.prevent="addRider" class="border-t border-border-light pt-4 space-y-4">
            <h3 class="font-bold">Add a Rider</h3>
            <div class="grid md:grid-cols-3 gap-4">
              <div><label for="rider-first" class="form-label">Rider first name</label><input id="rider-first" v-model="newRider.first_name" class="form-input" required /></div>
              <div><label for="rider-last" class="form-label">Rider last name</label><input id="rider-last" v-model="newRider.last_name" class="form-input" required /></div>
              <div><label for="rider-birthday" class="form-label">Date of birth</label><input id="rider-birthday" v-model="newRider.date_of_birth" class="form-input" type="date" required /></div>
            </div>
            <button class="btn-secondary" :disabled="busy">Save Rider</button>
          </form>
        </div>
        <div class="card card-padding space-y-4">
          <h2 class="text-xl font-serif font-bold">Your Weekly Reservations</h2>
          <p v-if="!account.reservations.length" class="text-light-brown">You have no weekly reservations yet.</p>
          <div v-for="reservation in account.reservations" :key="reservation.id" class="border-t border-border-light pt-4 space-y-3">
            <p class="font-bold">{{ reservation.lesson_name }} · Every {{ weekdayNames[reservation.day_of_week] }} at {{ formatTimeForDisplay(reservation.start_time) }}</p>
            <p class="text-sm text-light-brown">{{ reservation.rider_names.join(', ') }} · {{ reservation.business_timezone }} · {{ reservation.status }}</p>
            <template v-if="reservation.status === 'active'">
              <ul class="space-y-2">
                <li v-for="session in reservation.upcoming_sessions.slice(0, 4)" :key="session.date" class="flex flex-wrap items-center justify-between gap-2">
                  <span>{{ format(parseISO(session.date), 'EEE, MMM d, yyyy') }}</span>
                  <span v-if="session.skipped">Skipped</span>
                  <button v-else class="btn-secondary text-sm" :disabled="busy" @click="updateReservation(reservation.id, 'skip', session.date)">Skip this week</button>
                </li>
              </ul>
              <button class="btn-secondary text-sm" :disabled="busy" @click="updateReservation(reservation.id, 'cancel')">Cancel Weekly Reservation</button>
            </template>
          </div>
        </div>
      </template>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { format, parseISO } from 'date-fns'
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import { api } from '@/lib/api'
import { loadRiderAccount, type RiderAccount } from '@/services/riderAccountService'
import { weekdayNames } from '@/services/reservationService'
import { formatTimeForDisplay } from '@/services/availabilityService'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const account = ref<RiderAccount | null>(null)
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const notice = ref('')
const contact = ref({ first_name: '', last_name: '', phone: '' })
const newRider = ref({ first_name: '', last_name: '', date_of_birth: '' })
async function load() {
  account.value = await loadRiderAccount()
  if (!account.value) {
    await router.replace('/rider/sign-in')
    return
  }
  contact.value = { ...account.value.customer, phone: account.value.customer.phone || '' }
}
onMounted(async () => {
  try { await load() } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to load your account.' } finally { loading.value = false }
})
async function perform(action: () => Promise<void>, message: string) {
  busy.value = true
  error.value = ''
  notice.value = ''
  try { await action(); await load(); notice.value = message } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to save your changes.' } finally { busy.value = false }
}
async function saveContact() {
  await perform(async () => {
    const result = await api('PATCH', '/rider/account', contact.value)
    if (result.error) throw new Error(result.error)
  }, 'Contact details saved.')
}
async function addRider() {
  await perform(async () => {
    const result = await api('POST', '/rider/account/riders', newRider.value)
    if (result.error) throw new Error(result.error)
    newRider.value = { first_name: '', last_name: '', date_of_birth: '' }
  }, 'Rider saved. Matching existing profiles are reused.')
}
async function updateReservation(id: string, action: 'skip' | 'cancel', date?: string) {
  const message = action === 'cancel' ? 'Cancel this weekly reservation and release all future weeks?' : `Skip the session on ${date}? Your weekly reservation will continue.`
  if (!window.confirm(message)) return
  await perform(async () => {
    const result = await api('POST', `/rider/account/reservations/${encodeURIComponent(id)}/${action}`, { date })
    if (result.error) throw new Error(result.error)
  }, action === 'cancel' ? 'Weekly reservation cancelled.' : 'Session skipped. Your weekly reservation remains active.')
}
async function logout() {
  await perform(async () => {
    const result = await api('POST', '/rider/auth/logout')
    if (result.error) throw new Error(result.error)
    await useAuthStore().checkAdminStatus()
    await router.push('/rider/sign-in')
  }, '')
}
</script>
