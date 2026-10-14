<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-serif font-bold text-text-dark">Dashboard</h1>
        <p class="text-light-brown">Welcome back, Rylie!</p>
      </div>

      <!-- Loading State -->
      <div v-if="adminStore.isLoading" class="p-8 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-blue-700">Loading admin data...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="adminStore.error" class="p-4 bg-red-50 rounded-lg border border-red-200">
        <p class="text-red-700 font-semibold">Error loading data</p>
        <p class="text-red-600 text-sm">{{ adminStore.error }}</p>
      </div>

      <!-- Main Content -->
      <template v-else>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="card card-padding">
            <p class="text-light-brown text-sm mb-2">Total Riders</p>
            <p class="text-3xl font-bold text-accent-gold">{{ adminStore.riders.length }}</p>
          </div>
          <div class="card card-padding">
            <p class="text-light-brown text-sm mb-2">Active Horses</p>
            <p class="text-3xl font-bold text-accent-gold">{{ activeHorses }}</p>
          </div>
          <div class="card card-padding">
            <p class="text-light-brown text-sm mb-2">Total Bookings</p>
            <p class="text-3xl font-bold text-accent-gold">{{ adminStore.bookings.length }}</p>
          </div>
          <div class="card card-padding">
            <p class="text-light-brown text-sm mb-2">Upcoming (7 days)</p>
            <p class="text-3xl font-bold text-accent-gold">{{ upcomingBookings.length }}</p>
          </div>
        </div>

        <p v-if="calendarError" role="alert" class="text-red-700">
          {{ calendarError }}
          <button class="underline ml-2" @click="loadCalendar">Try again</button>
        </p>
        <p v-else-if="calendarLoading" role="status" class="text-light-brown">Loading the monthly calendar…</p>
        <BookingCalendar v-else :bookings="adminStore.bookings" :reservations="weeklyReservations"
          :customers="adminStore.customers" :lessons="adminStore.lessonTypes"
          :horses="adminStore.horses" :timezone="calendarTimezone" />

        <!-- Today's Lessons -->
        <div class="card card-padding">
          <h2 class="text-2xl font-serif font-bold text-text-dark mb-6">Today's Schedule</h2>
          <div v-if="todayBookings.length === 0" class="text-light-brown">
            No lessons scheduled for today.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="booking in todayBookings"
              :key="booking.id"
              class="p-4 border border-border-light rounded-lg flex justify-between items-start"
            >
              <div>
                <p class="font-bold">{{ formatTime(booking.startTime) }}</p>
                <p class="text-light-brown text-sm">
                  {{ getCustomerName(booking.customerId) }}
                </p>
                <p class="text-light-brown text-sm">
                  {{ getLessonName(booking.lessonTypeId) }}
                </p>
              </div>
              <span
                class="badge"
                :class="statusClasses(booking.status)"
              >
                {{ booking.status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <router-link
            to="/admin/bookings"
            class="card card-padding hover:shadow-lg cursor-pointer transition-shadow"
          >
            <h3 class="font-bold text-text-dark mb-2">📅 View All Bookings</h3>
            <p class="text-sm text-light-brown">Manage all lesson bookings</p>
          </router-link>
          <router-link
            to="/admin/riders"
            class="card card-padding hover:shadow-lg cursor-pointer transition-shadow"
          >
            <h3 class="font-bold text-text-dark mb-2">👥 Manage Riders</h3>
            <p class="text-sm text-light-brown">Add and edit rider information</p>
          </router-link>
          <router-link
            to="/admin/horses"
            class="card card-padding hover:shadow-lg cursor-pointer transition-shadow"
          >
            <h3 class="font-bold text-text-dark mb-2">🐴 Manage Horses</h3>
            <p class="text-sm text-light-brown">Update horse availability</p>
          </router-link>
          <router-link
            to="/admin/availability"
            class="card card-padding hover:shadow-lg cursor-pointer transition-shadow"
          >
            <h3 class="font-bold text-text-dark mb-2">📆 Availability</h3>
            <p class="text-sm text-light-brown">Set schedule and block times</p>
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { format, parseISO, isToday } from 'date-fns'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import BookingCalendar from '@/components/admin/BookingCalendar.vue'
import { fetchWeeklyReservations, type WeeklyReservation } from '@/services/reservationService'
import { fetchBusinessTimezone } from '@/services/apiService'

const adminStore = useAdminStore()
const weeklyReservations = ref<WeeklyReservation[]>([])
const calendarTimezone = ref('America/Denver')
const calendarLoading = ref(true)
const calendarError = ref('')

async function loadCalendar() {
  calendarLoading.value = true
  calendarError.value = ''
  try {
    const [reservations, timezone] = await Promise.all([fetchWeeklyReservations(), fetchBusinessTimezone()])
    weeklyReservations.value = reservations
    calendarTimezone.value = timezone || 'America/Denver'
  } catch (err) {
    calendarError.value = err instanceof Error ? err.message : 'Unable to load the calendar.'
  } finally {
    calendarLoading.value = false
  }
}
onMounted(loadCalendar)

const activeHorses = computed(() => {
  return adminStore.horses.filter(h => h.active).length
})

const upcomingBookings = computed(() => {
  return adminStore.getUpcomingBookings(7)
})

const todayBookings = computed(() => {
  return adminStore.bookings.filter(b =>
    b.status !== 'cancelled' && isToday(parseISO(b.startTime))
  ).sort((a, b) => new Date(a.startTime).getTime() - new Date(b.startTime).getTime())
})

const getCustomerName = (customerId: string) => {
  const customer = adminStore.getCustomer(customerId)
  return customer ? `${customer.firstName} ${customer.lastName}` : 'Unknown'
}

const getLessonName = (lessonTypeId: string) => {
  const lesson = adminStore.lessonTypes.find(l => l.id === lessonTypeId)
  return lesson?.name || 'Unknown'
}

const formatTime = (datetime: string) => {
  return format(parseISO(datetime), 'h:mm a')
}

const statusClasses = (status: string) => {
  switch (status) {
    case 'confirmed':
      return 'badge-success'
    case 'pending':
      return 'badge-warning'
    case 'cancelled':
      return 'badge-error'
    default:
      return 'badge-info'
  }
}
</script>
