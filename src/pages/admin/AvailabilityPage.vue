<template>
  <AdminLayout>
    <div class="space-y-8">
      <h1 class="text-3xl font-serif font-bold text-text-dark">Availability Management</h1>

      <!-- Instructor Availability -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">📅 Your Weekly Schedule</h2>
          <p class="text-light-brown text-sm mt-2">
            Set your availability for each day of the week. Lessons can only be booked during these times.
          </p>
        </div>

        <div v-if="adminStore.isLoading" class="p-6 text-center text-light-brown">
          Loading availability...
        </div>
        <div v-else-if="adminStore.instructorAvailability.length === 0" class="p-6 text-center text-light-brown">
          No availability configured yet.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Day</th>
                <th class="p-4 font-bold text-text-dark">Start Time</th>
                <th class="p-4 font-bold text-text-dark">End Time</th>
                <th class="p-4 font-bold text-text-dark">Active</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="slot in adminStore.instructorAvailability"
                :key="slot.id"
                class="border-b border-border-light hover:bg-gray-50"
              >
                <td class="p-4 font-medium">{{ dayName(slot.dayOfWeek) }}</td>
                <td class="p-4">
                  <input
                    :value="editingInstructor[slot.id]?.startTime"
                    @input="e => {
                      if (editingInstructor[slot.id]) {
                        editingInstructor[slot.id].startTime = (e.target as HTMLInputElement).value
                      }
                    }"
                    type="time"
                    class="form-input w-32"
                  />
                </td>
                <td class="p-4">
                  <input
                    :value="editingInstructor[slot.id]?.endTime"
                    @input="e => {
                      if (editingInstructor[slot.id]) {
                        editingInstructor[slot.id].endTime = (e.target as HTMLInputElement).value
                      }
                    }"
                    type="time"
                    class="form-input w-32"
                  />
                </td>
                <td class="p-4">
                  <input
                    :checked="editingInstructor[slot.id]?.active"
                    @change="e => {
                      if (editingInstructor[slot.id]) {
                        editingInstructor[slot.id].active = (e.target as HTMLInputElement).checked
                      }
                    }"
                    type="checkbox"
                    class="w-5 h-5"
                  />
                </td>
                <td class="p-4">
                  <button
                    @click="saveInstructorSlot(slot.id)"
                    class="text-xs px-3 py-1 bg-accent-gold text-white rounded hover:bg-warm-gold"
                  >
                    Save
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Horse Recurring Availability -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">🐴 Horse Availability</h2>
          <p class="text-light-brown text-sm mt-2">
            Set recurring availability for each horse. A horse can only be booked during these times.
          </p>
        </div>

        <div v-if="adminStore.isLoading" class="p-6 text-center text-light-brown">
          Loading horse availability...
        </div>
        <div v-else-if="adminStore.horseRecurringAvailability.length === 0" class="p-6 text-center text-light-brown">
          No horse availability configured yet.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Horse</th>
                <th class="p-4 font-bold text-text-dark">Day</th>
                <th class="p-4 font-bold text-text-dark">Start Time</th>
                <th class="p-4 font-bold text-text-dark">End Time</th>
                <th class="p-4 font-bold text-text-dark">Active</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="slot in adminStore.horseRecurringAvailability"
                :key="slot.id"
                class="border-b border-border-light hover:bg-gray-50"
              >
                <td class="p-4">{{ getHorseName(slot.horseId) }}</td>
                <td class="p-4 font-medium">{{ dayName(slot.dayOfWeek) }}</td>
                <td class="p-4">
                  <input
                    :value="editingHorse[slot.id]?.startTime"
                    @input="e => {
                      if (editingHorse[slot.id]) {
                        editingHorse[slot.id].startTime = (e.target as HTMLInputElement).value
                      }
                    }"
                    type="time"
                    class="form-input w-32"
                  />
                </td>
                <td class="p-4">
                  <input
                    :value="editingHorse[slot.id]?.endTime"
                    @input="e => {
                      if (editingHorse[slot.id]) {
                        editingHorse[slot.id].endTime = (e.target as HTMLInputElement).value
                      }
                    }"
                    type="time"
                    class="form-input w-32"
                  />
                </td>
                <td class="p-4">
                  <input
                    :checked="editingHorse[slot.id]?.active"
                    @change="e => {
                      if (editingHorse[slot.id]) {
                        editingHorse[slot.id].active = (e.target as HTMLInputElement).checked
                      }
                    }"
                    type="checkbox"
                    class="w-5 h-5"
                  />
                </td>
                <td class="p-4">
                  <button
                    @click="saveHorseSlot(slot.id)"
                    class="text-xs px-3 py-1 bg-accent-gold text-white rounded hover:bg-warm-gold"
                  >
                    Save
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Blocked Times -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">🚫 Blocked Times</h2>
          <p class="text-light-brown text-sm mt-2">
            Mark specific dates and times as unavailable (vet appointments, vacation, etc.).
          </p>
        </div>

        <!-- Add Blocked Time Form -->
        <div class="card-padding border-b border-border-light bg-gray-50">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="form-label">Type</label>
              <select v-model="blockForm.horseId" class="form-select">
                <option value="">Instructor-wide</option>
                <option v-for="horse in adminStore.horses" :key="horse.id" :value="horse.id">
                  {{ horse.name }} (horse-specific)
                </option>
              </select>
            </div>
            <div>
              <label class="form-label">Date</label>
              <input v-model="blockForm.date" type="date" class="form-input" />
            </div>
            <div>
              <label class="form-label">Start Time</label>
              <input v-model="blockForm.startTime" type="time" class="form-input" />
            </div>
            <div>
              <label class="form-label">End Time</label>
              <input v-model="blockForm.endTime" type="time" class="form-input" />
            </div>
            <div>
              <label class="form-label">Reason</label>
              <input v-model="blockForm.reason" type="text" placeholder="Vet, Vacation, etc." class="form-input" />
            </div>
          </div>
          <button
            @click="addBlockedTime"
            :disabled="!blockForm.date || !blockForm.startTime || !blockForm.endTime"
            class="btn-primary mt-4"
          >
            + Add Blocked Time
          </button>
        </div>

        <!-- Blocked Times Table -->
        <div v-if="adminStore.blockedTimes.length === 0" class="p-6 text-center text-light-brown">
          No blocked times.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Type</th>
                <th class="p-4 font-bold text-text-dark">Date</th>
                <th class="p-4 font-bold text-text-dark">Start Time</th>
                <th class="p-4 font-bold text-text-dark">End Time</th>
                <th class="p-4 font-bold text-text-dark">Reason</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="block in adminStore.blockedTimes"
                :key="block.id"
                class="border-b border-border-light hover:bg-gray-50"
              >
                <td class="p-4">
                  {{ block.horseId ? getHorseName(block.horseId) : 'Instructor' }}
                </td>
                <td class="p-4 text-sm">{{ block.date }}</td>
                <td class="p-4 text-sm">{{ block.startTime }}</td>
                <td class="p-4 text-sm">{{ block.endTime }}</td>
                <td class="p-4 text-sm">{{ block.reason || '—' }}</td>
                <td class="p-4">
                  <button
                    @click="deleteBlockedTime(block.id)"
                    class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import type { InstructorAvailability, HorseRecurringAvailability, BlockedTime } from '@/types/models'

const adminStore = useAdminStore()

const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const dayName = (dayOfWeek: number) => days[dayOfWeek]

// Initialize editing state for instructor slots
const editingInstructor = reactive<Record<string, Partial<InstructorAvailability>>>({})

// Initialize editing state for horse slots
const editingHorse = reactive<Record<string, Partial<HorseRecurringAvailability>>>({})

// Watch for data changes and update editing state
watch(() => adminStore.instructorAvailability, (newSlots) => {
  Object.keys(editingInstructor).forEach(key => {
    delete editingInstructor[key]
  })
  newSlots.forEach(slot => {
    editingInstructor[slot.id] = { ...slot }
  })
}, { immediate: true })

watch(() => adminStore.horseRecurringAvailability, (newSlots) => {
  Object.keys(editingHorse).forEach(key => {
    delete editingHorse[key]
  })
  newSlots.forEach(slot => {
    editingHorse[slot.id] = { ...slot }
  })
}, { immediate: true })

const blockForm = ref({
  horseId: '',
  date: '',
  startTime: '',
  endTime: '',
  reason: '',
})

const getHorseName = (horseId?: string) => {
  if (!horseId) return 'Instructor'
  const horse = adminStore.getHorse(horseId)
  return horse?.name || 'Unknown'
}

const saveInstructorSlot = async (slotId: string) => {
  const updates = editingInstructor[slotId]
  if (!updates) return

  const success = await adminStore.updateInstructorAvailabilityLocal(slotId, updates)
  if (success) {
    alert('Instructor availability updated')
  } else {
    alert('Failed to update instructor availability')
  }
}

const saveHorseSlot = async (slotId: string) => {
  const updates = editingHorse[slotId]
  if (!updates) return

  const success = await adminStore.updateHorseAvailabilityLocal(slotId, updates)
  if (success) {
    alert('Horse availability updated')
  } else {
    alert('Failed to update horse availability')
  }
}

const addBlockedTime = async () => {
  if (!blockForm.value.date || !blockForm.value.startTime || !blockForm.value.endTime) {
    alert('Please fill in date and times')
    return
  }

  const newBlock: Omit<BlockedTime, 'id' | 'createdAt' | 'updatedAt'> = {
    horseId: blockForm.value.horseId || undefined,
    date: blockForm.value.date,
    startTime: blockForm.value.startTime,
    endTime: blockForm.value.endTime,
    reason: blockForm.value.reason || undefined,
  }

  const result = await adminStore.addBlockedTime(newBlock)
  if (result) {
    blockForm.value = { horseId: '', date: '', startTime: '', endTime: '', reason: '' }
    alert('Blocked time added')
  } else {
    alert('Failed to add blocked time')
  }
}

const deleteBlockedTime = async (blockId: string) => {
  if (!confirm('Delete this blocked time?')) return

  const success = await adminStore.deleteBlockedTime(blockId)
  if (success) {
    alert('Blocked time deleted')
  } else {
    alert('Failed to delete blocked time')
  }
}
</script>
