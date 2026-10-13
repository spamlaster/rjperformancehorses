<template>
  <AdminLayout>
    <div class="space-y-6">
      <h1 class="text-3xl font-serif font-bold text-text-dark">Bookings</h1>

      <!-- Filters -->
      <div class="card card-padding flex gap-4">
        <select v-model="statusFilter" class="form-select flex-1">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <!-- Bookings Table -->
      <div class="card">
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

              </tr>
            </thead>
            <tbody>
              <tr
                v-for="booking in filteredBookings"
                :key="booking.id"
                class="border-b border-border-light hover:bg-gray-50"
              >
                <td class="p-4">{{ formatDateTime(booking.startTime) }}</td>
                <td class="p-4 text-sm">{{ getCustomerName(booking.customerId) }}</td>
                <td class="p-4 text-sm">{{ getLessonName(booking.lessonTypeId) }}</td>
                <td class="p-4 text-sm">{{ booking.riderNames?.length ? booking.riderNames.join(', ') : '—' }}</td>
                <td class="p-4 text-sm">{{ getHorseName(booking.horseId) }}</td>
                <td class="p-4">
                  <span class="badge" :class="statusClasses(booking.status)">
                    {{ booking.status }}
                  </span>
                </td>

              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="filteredBookings.length === 0" class="p-4 text-center text-light-brown">
          No bookings found.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { format, parseISO } from 'date-fns'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'

const adminStore = useAdminStore()
const statusFilter = ref('')

const filteredBookings = computed(() => {
  return adminStore.bookings.filter(b =>
    !statusFilter.value || b.status === statusFilter.value
  )
})

const getCustomerName = (customerId: string) => {
  const customer = adminStore.customers.find(c => c.id === customerId)
  return customer ? `${customer.firstName} ${customer.lastName}` : 'Unknown'
}

const getLessonName = (lessonTypeId: string) => {
  const lesson = adminStore.lessonTypes.find(l => l.id === lessonTypeId)
  return lesson?.name || 'Unknown'
}

const getHorseName = (horseId: string) => {
  const horse = adminStore.getHorse(horseId)
  return horse?.name || 'Unknown'
}

const formatDateTime = (datetime: string) => {
  return format(parseISO(datetime), 'MMM dd, yyyy h:mm a')
}

const statusClasses = (status: string) => {
  switch (status) {
    case 'confirmed':
      return 'badge-success'
    case 'pending':
      return 'badge-warning'
    case 'cancelled':
      return 'badge-error'
    case 'completed':
      return 'badge-info'
    default:
      return 'badge-info'
  }
}

</script>
