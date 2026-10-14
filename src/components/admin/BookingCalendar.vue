<template>
  <section class="card overflow-hidden" aria-label="Monthly booking calendar">
    <div class="p-4 md:p-6 border-b border-border-light flex flex-wrap items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-serif font-bold text-text-dark">Lesson Calendar</h2>
        <p class="text-sm text-light-brown">{{ monthBookings.length }} lessons this month · {{ timezone }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn-secondary px-3" aria-label="Previous month" @click="changeMonth(-1)">‹</button>
        <h3 class="font-bold text-center min-w-[9rem]" aria-live="polite">{{ format(month, 'MMMM yyyy') }}</h3>
        <button class="btn-secondary px-3" aria-label="Next month" @click="changeMonth(1)">›</button>
        <button class="btn-secondary text-sm" @click="goToday">Today</button>
      </div>
    </div>
    <div class="grid grid-cols-7 bg-gray-50 border-b border-border-light" aria-hidden="true">
      <div v-for="day in weekdays" :key="day" class="py-3 text-center text-xs md:text-sm font-bold text-light-brown">{{ day }}</div>
    </div>
    <div class="grid grid-cols-7">
      <button v-for="day in days" :key="day.key" type="button"
        :aria-label="`${format(day.date, 'EEEE, MMMM d, yyyy')}, ${day.bookings.length} ${day.bookings.length === 1 ? 'lesson' : 'lessons'}`"
        :aria-pressed="selectedDate === day.key"
        class="min-w-0 min-h-[82px] md:min-h-[120px] p-1.5 md:p-3 text-left border-b border-r border-border-light transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-dark-brown focus-visible:-outline-offset-2"
        :class="selectedDate === day.key ? 'bg-yellow-50 ring-2 ring-inset ring-accent-gold' : day.inMonth ? 'bg-white hover:bg-gray-50' : 'bg-gray-50 text-gray-400'"
        @click="selectDate(day.date)">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm font-bold" :class="day.key === today ? 'bg-dark-brown text-white' : ''">{{ format(day.date, 'd') }}</span>
        <span v-if="day.bookings.length" class="block text-[10px] md:text-xs text-light-brown mt-1">{{ day.bookings.length }} {{ day.bookings.length === 1 ? 'lesson' : 'lessons' }}</span>
        <div class="hidden md:block space-y-1 mt-2">
          <p v-for="booking in day.bookings.slice(0, 2)" :key="booking.id" class="text-xs truncate rounded px-1.5 py-1 border-l-2"
            :class="booking.status === 'pending' ? 'bg-yellow-50 border-accent-gold' : 'bg-green-50 border-green-600'">
            {{ time(booking.startTime) }} · {{ customerName(booking.customerId) }}
          </p>
          <p v-if="day.bookings.length > 2" class="text-xs text-light-brown">+{{ day.bookings.length - 2 }} more</p>
        </div>
      </button>
    </div>
    <div class="p-4 md:p-6">
      <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
        <h3 class="text-xl font-serif font-bold">{{ format(parseISO(selectedDate), 'EEEE, MMMM d, yyyy') }}</h3>
        <router-link to="/admin/bookings" class="text-sm font-semibold text-dark-brown underline">Manage bookings</router-link>
      </div>
      <p v-if="!selectedBookings.length" class="text-light-brown">No lessons booked for this day.</p>
      <ul v-else class="space-y-3">
        <li v-for="booking in selectedBookings" :key="booking.id" class="border border-border-light rounded-lg p-4 flex flex-wrap justify-between items-start gap-3">
          <div>
            <p class="font-bold">{{ time(booking.startTime) }}–{{ time(booking.endTime) }} · {{ customerName(booking.customerId) }}</p>
            <p class="text-sm">{{ lessonName(booking.lessonTypeId) }}</p>
            <p class="text-sm text-light-brown">{{ booking.riderNames?.join(', ') || 'Rider details unavailable' }} · {{ horseName(booking.horseId) }}</p>
            <p v-if="booking.recurringLessonId" class="text-xs text-light-brown mt-1">Weekly reservation</p>
          </div>
          <span class="badge" :class="booking.status === 'pending' ? 'badge-warning' : booking.status === 'confirmed' ? 'badge-success' : 'badge-info'">{{ booking.status }}</span>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { addDays, addMonths, endOfMonth, endOfWeek, format, parseISO, startOfMonth, startOfWeek } from 'date-fns'
import type { Booking, Customer, Horse, LessonType } from '@/types/models'
import type { WeeklyReservation } from '@/services/reservationService'

const props = defineProps<{
  bookings: Booking[]
  reservations: WeeklyReservation[]
  customers: Customer[]
  lessons: LessonType[]
  horses: Horse[]
  timezone: string
}>()
const businessToday = () => {
  const parts = new Intl.DateTimeFormat('en-US', { timeZone: props.timezone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date())
  const part = (type: string) => parts.find(p => p.type === type)?.value
  return `${part('year')}-${part('month')}-${part('day')}`
}
const today = computed(businessToday)
const month = ref(startOfMonth(parseISO(today.value)))
const selectedDate = ref(today.value)
const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
watch(() => props.timezone, () => goToday())
const rangeStart = computed(() => startOfWeek(startOfMonth(month.value)))
const rangeEnd = computed(() => endOfWeek(endOfMonth(month.value)))

const calendarBookings = computed(() => {
  const byOccurrence = new Map<string, Booking>()
  const key = (booking: Booking) => booking.recurringLessonId ? `${booking.recurringLessonId}:${booking.startTime.slice(0, 10)}` : booking.id
  // Actual dated rows take precedence over generated occurrences, including cancelled sessions.
  for (const booking of props.bookings) byOccurrence.set(key(booking), booking)
  for (const reservation of props.reservations) {
    if (reservation.status !== 'active') continue
    for (let day = rangeStart.value; day <= rangeEnd.value; day = addDays(day, 1)) {
      const date = format(day, 'yyyy-MM-dd')
      if (day.getDay() !== reservation.day_of_week || (reservation.starts_on && date < reservation.starts_on)
        || reservation.skipped_dates.includes(date)) continue
      const id = `${reservation.id}:${date}`
      if (byOccurrence.has(id)) continue
      const startTime = `${date}T${reservation.start_time.slice(0, 5)}:00`
      const [hour, minute] = reservation.start_time.split(':').map(Number)
      const endMinutes = hour * 60 + minute + reservation.duration_minutes
      const endDate = format(addDays(day, Math.floor(endMinutes / 1440)), 'yyyy-MM-dd')
      const endClock = `${String(Math.floor(endMinutes / 60) % 24).padStart(2, '0')}:${String(endMinutes % 60).padStart(2, '0')}:00`
      byOccurrence.set(id, {
        id, customerId: reservation.customer_id, lessonTypeId: reservation.lesson_type_id,
        horseId: reservation.horse_ids[0] || '', startTime, endTime: `${endDate}T${endClock}`,
        status: 'confirmed', bookingType: 'recurring', recurringLessonId: reservation.id,
        riderNames: reservation.rider_names, createdAt: '', updatedAt: '',
      })
    }
  }
  return [...byOccurrence.values()].filter(b => b.status !== 'cancelled' && !b.deletedAt).sort((a, b) => a.startTime.localeCompare(b.startTime))
})
const byDate = computed(() => {
  const grouped = new Map<string, Booking[]>()
  for (const booking of calendarBookings.value) {
    const date = booking.startTime.slice(0, 10)
    grouped.set(date, [...(grouped.get(date) || []), booking])
  }
  return grouped
})
const days = computed(() => {
  const result = []
  for (let date = rangeStart.value; date <= rangeEnd.value; date = addDays(date, 1)) {
    const key = format(date, 'yyyy-MM-dd')
    result.push({ date, key, inMonth: date.getMonth() === month.value.getMonth(), bookings: byDate.value.get(key) || [] })
  }
  return result
})
const monthBookings = computed(() => calendarBookings.value.filter(b => b.startTime.slice(0, 7) === format(month.value, 'yyyy-MM')))
const selectedBookings = computed(() => byDate.value.get(selectedDate.value) || [])
function changeMonth(amount: number) {
  month.value = startOfMonth(addMonths(month.value, amount))
  selectedDate.value = format(month.value, 'yyyy-MM-dd')
}
function selectDate(date: Date) {
  selectedDate.value = format(date, 'yyyy-MM-dd')
  month.value = startOfMonth(date)
}
function goToday() {
  month.value = startOfMonth(parseISO(businessToday()))
  selectedDate.value = businessToday()
}
const time = (value: string) => format(parseISO(value), 'h:mm a')
const customerName = (id: string) => {
  const customer = props.customers.find(c => c.id === id)
  return customer ? `${customer.firstName} ${customer.lastName}` : 'Unknown customer'
}
const lessonName = (id: string) => props.lessons.find(l => l.id === id)?.name || 'Lesson'
const horseName = (id: string) => props.horses.find(h => h.id === id)?.name || 'Horse not assigned'
</script>
