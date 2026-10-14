<template>
  <AdminLayout>
    <div class="space-y-6">
      <h1 class="text-3xl font-serif font-bold text-text-dark">Bookings</h1>
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
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { format, parseISO } from 'date-fns'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import { fetchBookings } from '@/services/apiService'
import { fetchWeeklyReservations, cancelAdminReservation, cancelAdminBooking, weekdayNames, type WeeklyReservation } from '@/services/reservationService'
import { formatTimeForDisplay } from '@/services/availabilityService'
import type { Booking } from '@/types/models'

const adminStore = useAdminStore()
const statusFilter = ref('')
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
