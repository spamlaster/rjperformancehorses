<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center gap-4">
        <h1 class="text-3xl font-serif font-bold text-text-dark">Bookings</h1>
        <button class="btn-primary" @click="showAddSession = true">+ Add Session</button>
      </div>
      <AddSessionForm v-if="showAddSession" :rider-id="selectedRiderId" @cancel="showAddSession = false" @saved="sessionSaved" />
      <p v-if="actionError" role="alert" class="text-red-700">{{ actionError }}</p>
      <p v-if="notice" role="status">{{ notice }}</p>

      <div class="card card-padding">
        <h2 class="text-2xl font-serif font-bold mb-3">Standing Weekly Reservations</h2>
        <p class="text-light-brown mb-4">These times stay reserved indefinitely. Cancelling releases all future weeks.</p>
        <p v-if="loadingReservations" role="status">Loading weekly reservations…</p>
        <p v-else-if="!weeklyReservations.length" class="text-light-brown">No weekly reservations found.</p>
        <div class="space-y-4">
          <div v-for="reservation in weeklyReservations" :key="reservation.id" class="border-b border-border-light pb-4 flex flex-wrap justify-between items-start gap-4">
            <div>
              <p class="font-bold">{{ weekdayNames[reservation.day_of_week] }} at {{ formatTimeForDisplay(reservation.start_time) }}</p>
              <p>{{ getCustomerName(reservation.customer_id) }} · {{ reservation.lesson_name }}</p>
              <p class="text-sm text-light-brown">{{ reservation.rider_names.join(', ') }} · {{ reservation.duration_minutes }} minutes · {{ reservation.status }}</p>
              <p v-if="reservation.skipped_dates.length" class="text-sm text-light-brown">Skipped dates: {{ reservation.skipped_dates.join(', ') }}</p>
            </div>
            <button v-if="reservation.status === 'active'" class="btn-secondary text-sm" :disabled="!!busyId" @click="cancelWeekly(reservation.id)">Cancel Weekly Reservation</button>
          </div>
        </div>
      </div>

      <div class="card card-padding flex gap-4">
        <select v-model="statusFilter" class="form-select flex-1" aria-label="Filter bookings by status">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div class="card">
        <p class="p-4 text-sm text-light-brown">Dated bookings and the next eight weeks of standing reservations.</p>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Date & Time</th>
                <th class="p-4 font-bold text-text-dark">Customer</th>
                <th class="p-4 font-bold text-text-dark">Lesson</th>
                <th class="p-4 font-bold text-text-dark">Riders</th>
                <th class="p-4 font-bold text-text-dark">Horse</th>
                <th class="p-4 font-bold text-text-dark">Status</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in filteredBookings" :key="booking.id" class="border-b border-border-light hover:bg-gray-50">
                <td class="p-4">{{ formatDateTime(booking.startTime) }}<span v-if="booking.recurringLessonId" class="block text-xs text-light-brown">Weekly reservation</span></td>
                <td class="p-4 text-sm">{{ getCustomerName(booking.customerId) }}</td>
                <td class="p-4 text-sm">{{ getLessonName(booking.lessonTypeId) }}</td>
                <td class="p-4 text-sm">{{ booking.riderNames?.length ? booking.riderNames.join(', ') : '—' }}</td>
                <td class="p-4 text-sm">{{ getHorseName(booking.horseId) }}</td>
                <td class="p-4"><span class="badge" :class="statusClasses(booking.status)">{{ booking.status }}</span></td>
                <td class="p-4">
                  <button v-if="booking.waiverCount" class="text-sm underline block mb-2" @click="viewWaivers(booking)">View Signed Waivers ({{ booking.waiverCount }})</button>
                  <span v-else class="text-xs text-light-brown block mb-2">No signed waiver on file</span>
                  <button v-if="canCancel(booking)" class="btn-secondary text-sm" :disabled="!!busyId" @click="cancelBooking(booking)">
                    {{ booking.recurringLessonId ? 'Cancel Weekly Reservation' : 'Cancel Booking' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="filteredBookings.length === 0" class="p-4 text-center text-light-brown">No bookings found.</div>
      </div>
      <dialog ref="waiverDialog" class="w-[calc(100%-2rem)] max-w-2xl max-h-[90vh] rounded-lg p-6 backdrop:bg-black/40" aria-labelledby="signed-waiver-heading">
        <div class="flex items-center justify-between gap-4 mb-4">
          <h2 id="signed-waiver-heading" class="text-2xl font-serif font-bold">Signed Waivers</h2>
          <button class="btn-secondary text-sm" @click="waiverDialog?.close()">Close</button>
        </div>
        <p v-if="waiversLoading" role="status">Loading signed waivers…</p>
        <p v-if="waiversError" role="alert" class="text-red-700">{{ waiversError }}</p>
        <div v-for="record in signedWaivers" :key="record.id" class="border-t border-border-light pt-4 mt-4 space-y-2">
          <h3 class="font-bold">{{ record.rider_name }}</h3>
          <p>Signed by {{ record.signer_name }} ({{ record.relationship_to_rider.replace('_', ' ') }})</p>
          <p class="text-sm text-light-brown">{{ record.signer_email }} · {{ record.accepted_at }}</p>
          <p class="font-bold">{{ record.waiver_title || 'Waiver' }}<span v-if="record.waiver_version"> · Version {{ record.waiver_version }}</span></p>
          <p class="whitespace-pre-wrap text-sm bg-gray-50 rounded p-4">{{ record.waiver_content || 'A text snapshot is not available for this older record.' }}</p>
        </div>
      </dialog>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AddSessionForm from '@/components/admin/AddSessionForm.vue'
import { format, parseISO } from 'date-fns'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import { fetchBookings } from '@/services/apiService'
import { fetchWeeklyReservations, cancelAdminReservation, cancelAdminBooking, weekdayNames, type WeeklyReservation } from '@/services/reservationService'
import { formatTimeForDisplay } from '@/services/availabilityService'
import type { Booking } from '@/types/models'
import { fetchSignedWaivers, type SignedWaiver } from '@/services/waiverService'

const adminStore = useAdminStore()
const route = useRoute()
const selectedRiderId = typeof route.query.rider === 'string' ? route.query.rider : undefined
const showAddSession = ref(!!selectedRiderId)
async function sessionSaved() {
  showAddSession.value = false
  notice.value = 'Session saved. The selected time is now booked.'
  try {
    await refresh()
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : 'Session saved, but the booking list could not refresh.'
  }
}
const statusFilter = ref('')
const waiverDialog = ref<HTMLDialogElement | null>(null)
const signedWaivers = ref<SignedWaiver[]>([])
const waiversLoading = ref(false)
const waiversError = ref('')
async function viewWaivers(booking: Booking) {
  signedWaivers.value = []
  waiversError.value = ''
  waiversLoading.value = true
  waiverDialog.value?.showModal()
  try {
    signedWaivers.value = await fetchSignedWaivers(booking.recurringLessonId || booking.id, !!booking.recurringLessonId)
  } catch (err) {
    waiversError.value = err instanceof Error ? err.message : 'Unable to load signed waivers.'
  } finally {
    waiversLoading.value = false
  }
}
const weeklyReservations = ref<WeeklyReservation[]>([])
const loadingReservations = ref(true)
const busyId = ref('')
const actionError = ref('')
const notice = ref('')
const filteredBookings = computed(() => adminStore.bookings.filter(b => !statusFilter.value || b.status === statusFilter.value))

async function refresh() {
  weeklyReservations.value = await fetchWeeklyReservations()
  adminStore.bookings = await fetchBookings()
}

onMounted(async () => {
  try {
    await refresh()
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : 'Unable to load weekly reservations.'
  } finally {
    loadingReservations.value = false
  }
})

function canCancel(booking: Booking) {
  return booking.recurringLessonId
    ? weeklyReservations.value.some(r => r.id === booking.recurringLessonId && r.status === 'active')
    : ['pending', 'confirmed'].includes(booking.status)
}

async function cancelWeekly(id: string) {
  if (!window.confirm('Cancel this weekly reservation and release its time for all future weeks?')) return
  await performCancellation(id, () => cancelAdminReservation(id))
}

async function cancelBooking(booking: Booking) {
  if (booking.recurringLessonId) {
    await cancelWeekly(booking.recurringLessonId)
    return
  }
  if (!window.confirm('Cancel this booking and release its time?')) return
  await performCancellation(booking.id, () => cancelAdminBooking(booking.id))
}

async function performCancellation(id: string, action: () => Promise<void>) {
  busyId.value = id
  actionError.value = ''
  notice.value = ''
  try {
    await action()
    await refresh()
    notice.value = 'Reservation cancelled. The time is available again.'
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : 'Unable to cancel the reservation.'
  } finally {
    busyId.value = ''
  }
}

const getCustomerName = (id: string) => {
  const customer = adminStore.customers.find(c => c.id === id)
  return customer ? `${customer.firstName} ${customer.lastName}` : 'Unknown'
}
const getLessonName = (id: string) => adminStore.lessonTypes.find(l => l.id === id)?.name || 'Unknown'
const getHorseName = (id: string) => adminStore.getHorse(id)?.name || 'Unknown'
const formatDateTime = (datetime: string) => format(parseISO(datetime), 'MMM dd, yyyy h:mm a')
const statusClasses = (status: string) => ({
  confirmed: 'badge-success',
  pending: 'badge-warning',
  cancelled: 'badge-error',
  completed: 'badge-info',
}[status] || 'badge-info')
</script>
