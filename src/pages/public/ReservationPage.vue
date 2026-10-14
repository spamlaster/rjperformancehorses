<template>
  <PublicLayout>
    <section class="container-narrow py-12 md:py-16">
      <div class="card card-padding max-w-2xl mx-auto">
        <h1 class="text-3xl font-serif font-bold mb-4">Your Weekly Reservation</h1>
        <p v-if="loading" role="status">Loading your reservation…</p>
        <div v-else-if="!reservation">
          <p class="text-light-brown mb-4">Open the private management link saved when you booked, or paste that link below.</p>
          <form @submit.prevent="openLink" class="space-y-3">
            <label for="reservation-link" class="form-label">Private management link</label>
            <input id="reservation-link" v-model="linkInput" type="text" class="form-input" required />
            <button type="submit" class="btn-primary">View Reservation</button>
          </form>
        </div>
        <template v-else>
          <p class="text-lg font-bold">{{ reservation.lesson_name }}</p>
          <p class="mt-2">Every {{ weekdayNames[reservation.day_of_week] }} at {{ formatTimeForDisplay(reservation.start_time) }}</p>
          <p class="text-sm text-light-brown">{{ reservation.duration_minutes }} minutes · {{ reservation.business_timezone }}</p>
          <p class="mt-2">{{ reservation.rider_names.join(', ') }}</p>
          <p v-if="reservation.status === 'cancelled'" class="mt-6" role="status">
            Your weekly reservation is cancelled. All future weeks have been released.
          </p>
          <template v-else>
            <p class="mt-4 text-light-brown">This time is yours every week until you cancel. Skipping one session keeps your weekly time reserved.</p>
            <h2 class="text-xl font-serif font-bold mt-6 mb-3">Upcoming sessions</h2>
            <ul class="space-y-3">
              <li v-for="session in reservation.upcoming_sessions" :key="session.date" class="flex flex-wrap justify-between items-center gap-3 border-b border-border-light pb-3">
                <span>{{ formatDate(session.date) }}</span>
                <span v-if="session.skipped" class="text-light-brown">Skipped</span>
                <button v-else class="btn-secondary text-sm" :disabled="busy" @click="skip(session.date)" :aria-label="`Skip session on ${formatDate(session.date)}`">Skip this week</button>
              </li>
            </ul>
            <div class="mt-8 border-t border-border-light pt-5">
              <p class="mb-3">Cancelling ends your standing reservation and releases this time for all following weeks.</p>
              <button class="btn-secondary" :disabled="busy" @click="cancel">Cancel Weekly Reservation</button>
            </div>
          </template>
        </template>
        <p v-if="error" role="alert" class="text-red-700 mt-4">{{ error }}</p>
        <p v-if="notice" role="status" class="mt-4">{{ notice }}</p>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { format, parseISO } from 'date-fns'
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import { manageReservation, weekdayNames, type WeeklyReservation } from '@/services/reservationService'
import { formatTimeForDisplay } from '@/services/availabilityService'

const reservation = ref<WeeklyReservation | null>(null)
const token = ref('')
const linkInput = ref('')
const loading = ref(false)
const busy = ref(false)
const error = ref('')
const notice = ref('')
const formatDate = (date: string) => format(parseISO(date), 'EEEE, MMMM d, yyyy')

async function load() {
  error.value = ''
  loading.value = true
  try {
    reservation.value = await manageReservation(token.value)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load your reservation.'
  } finally {
    loading.value = false
  }
}

async function openLink() {
  const value = linkInput.value.trim()
  token.value = value.includes('#') ? value.substring(value.lastIndexOf('#') + 1) : value
  if (!/^[a-f0-9]{64}$/.test(token.value)) {
    error.value = 'Paste the full private management link from your booking confirmation.'
    return
  }
  window.history.replaceState(null, '', `/reservation#${token.value}`)
  await load()
}

async function skip(date: string) {
  if (!window.confirm(`Skip your session on ${formatDate(date)}? Your weekly reservation will continue.`)) return
  await update('skip', date)
}

async function cancel() {
  if (!window.confirm('Cancel your weekly reservation? This releases your time for all future weeks.')) return
  await update('cancel')
}

async function update(action: 'skip' | 'cancel', date?: string) {
  busy.value = true
  error.value = ''
  notice.value = ''
  try {
    reservation.value = await manageReservation(token.value, action, date)
    notice.value = action === 'skip' ? 'Session skipped. Your weekly time is still reserved.' : 'Weekly reservation cancelled.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to update your reservation.'
  } finally {
    busy.value = false
  }
}

onMounted(async () => {
  token.value = window.location.hash.substring(1)
  if (token.value) await load()
})
</script>
