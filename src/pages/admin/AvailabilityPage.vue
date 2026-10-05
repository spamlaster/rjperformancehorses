<template>
  <AdminLayout>
    <div class="space-y-6">
      <h1 class="text-3xl font-serif font-bold text-text-dark">Availability Management</h1>

      <div class="card card-padding">
        <h2 class="text-xl font-bold mb-4">Block Time</h2>
        <p class="text-light-brown mb-4">
          Mark specific dates and times as unavailable for lessons.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="form-label">Horse</label>
            <select v-model="blockForm.horseId" class="form-select">
              <option value="">Select a horse...</option>
              <option v-for="horse in adminStore.horses" :key="horse.id" :value="horse.id">
                {{ horse.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="form-label">Date</label>
            <input v-model="blockForm.date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Time</label>
            <input v-model="blockForm.time" type="time" class="form-input" />
          </div>
        </div>

        <button
          @click="blockTime"
          :disabled="!blockForm.horseId || !blockForm.date || !blockForm.time"
          class="btn-primary mt-4"
        >
          Block Time
        </button>
      </div>

      <!-- Blocked Times -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">Blocked Times</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Horse</th>
                <th class="p-4 font-bold text-text-dark">Date</th>
                <th class="p-4 font-bold text-text-dark">Time</th>
                <th class="p-4 font-bold text-text-dark">Status</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="slot in blockedSlots"
                :key="slot.id"
                class="border-b border-border-light hover:bg-gray-50"
              >
                <td class="p-4">{{ getHorseName(slot.horseid) }}</td>
                <td class="p-4 text-sm">{{ slot.date }}</td>
                <td class="p-4 text-sm">{{ slot.startTime }}</td>
                <td class="p-4">
                  <span class="badge badge-warning">{{ slot.status }}</span>
                </td>
                <td class="p-4">
                  <button
                    @click="unblockTime(slot.id)"
                    class="text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600"
                  >
                    Unblock
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="blockedSlots.length === 0" class="p-4 text-center text-light-brown">
          No blocked times.
        </div>
      </div>

      <!-- Instructor Availability Info -->
      <div class="card card-padding bg-blue-50">
        <h2 class="text-lg font-bold mb-2">📝 Recurring Availability</h2>
        <p class="text-light-brown text-sm mb-4">
          Phase 1: Availability is managed through individual time slots.
        </p>
        <p class="text-light-brown text-sm mb-4">
          In Phase 2, we'll add recurring availability rules (e.g., "Available every Tuesday 3-7 PM") and exceptions.
        </p>
        <p class="text-light-brown text-sm">
          Currently showing {{ adminStore.timeSlots.length }} available time slots across all dates.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'

const adminStore = useAdminStore()

const blockForm = ref({
  horseId: '',
  date: '',
  time: '',
})

const blockedSlots = computed(() => {
  return adminStore.timeSlots.filter(s => s.status === 'maintenance')
})

const getHorseName = (horseId: string) => {
  const horse = adminStore.getHorse(horseId)
  return horse?.name || 'Unknown'
}

const blockTime = () => {
  if (!blockForm.value.horseId || !blockForm.value.date || !blockForm.value.time) {
    alert('Please fill in all fields')
    return
  }

  const timeSlots = adminStore.timeSlots.filter(
    s => s.horseid === blockForm.value.horseId &&
         s.date === blockForm.value.date &&
         s.startTime === blockForm.value.time
  )

  if (timeSlots.length === 0) {
    alert('No time slot found for this horse, date, and time')
    return
  }

  timeSlots.forEach(slot => {
    adminStore.blockTimeSlot(slot.id)
  })

  blockForm.value = { horseId: '', date: '', time: '' }
  alert('Time blocked successfully')
}

const unblockTime = (slotId: string) => {
  adminStore.unblockTimeSlot(slotId)
}
</script>
