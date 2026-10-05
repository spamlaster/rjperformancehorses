<template>
  <AdminLayout>
    <div class="space-y-8">
      <h1 class="text-3xl font-serif font-bold text-text-dark">Availability Management</h1>

      <!-- Instructor Availability -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">📅 Your Weekly Schedule</h2>
          <p class="text-light-brown text-sm mt-2">
            Configure your recurring availability for each day of the week.
          </p>
        </div>

        <div v-if="adminStore.isLoading" class="p-6 text-center text-light-brown">
          Loading availability...
        </div>
        <div v-else class="card-padding space-y-6">
          <div v-for="dayOfWeek in [0, 1, 2, 3, 4, 5, 6]" :key="dayOfWeek" class="space-y-3">
            <h3 class="font-bold text-text-dark">{{ dayName(dayOfWeek) }}</h3>

            <div class="pl-4 space-y-2 border-l-2 border-border-light">
              <div v-if="getInstructorWindowsForDay(dayOfWeek).length === 0" class="text-light-brown italic">
                Not available
              </div>

              <div v-for="slot in getInstructorWindowsForDay(dayOfWeek)" :key="slot.id" class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                <div class="flex-1">
                  <span class="font-medium">{{ formatTime(slot.startTime) }} – {{ formatTime(slot.endTime) }}</span>
                  <span v-if="!slot.active" class="ml-2 text-xs text-light-brown">(disabled)</span>
                </div>
                <label class="flex items-center gap-2">
                  <input v-model="slot.active" type="checkbox" class="w-4 h-4" />
                  <span class="text-sm">Active</span>
                </label>
                <button @click="removeInstructorWindow(slot.id)" class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                  Remove
                </button>
              </div>

              <button @click="showAddInstructorForm(dayOfWeek)" class="text-sm text-accent-gold hover:underline font-medium">
                + Add time window
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Instructor Window Form -->
      <div v-if="addInstructorFormDay !== null" class="card card-padding bg-blue-50 border border-blue-200">
        <h3 class="font-bold mb-4">Add {{ dayName(addInstructorFormDay) }} Time Window</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="form-label">Start Time</label>
            <input v-model="newInstructorWindow.startTime" type="time" class="form-input" />
          </div>
          <div>
            <label class="form-label">End Time</label>
            <input v-model="newInstructorWindow.endTime" type="time" class="form-input" />
          </div>
          <div>
            <label class="flex items-center gap-2 mt-8">
              <input v-model="newInstructorWindow.active" type="checkbox" />
              <span>Active</span>
            </label>
          </div>
        </div>
        <div class="flex gap-3">
          <button @click="addInstructorWindow" class="btn-primary">Add Window</button>
          <button @click="addInstructorFormDay = null" class="btn-secondary">Cancel</button>
        </div>
      </div>

      <!-- Horse Recurring Availability -->
      <div class="card">
        <div class="card-padding border-b border-border-light">
          <h2 class="text-xl font-bold">🐴 Horse Availability</h2>
          <p class="text-light-brown text-sm mt-2">
            Configure recurring availability for each horse.
          </p>
        </div>

        <div v-if="adminStore.isLoading" class="p-6 text-center text-light-brown">
          Loading horse availability...
        </div>
        <div v-else class="card-padding space-y-8">
          <div v-for="horse in adminStore.horses" :key="horse.id" class="border-b border-border-light pb-6">
            <h3 class="font-bold text-lg text-text-dark mb-4">{{ horse.name }}</h3>

            <div class="space-y-6 pl-4">
              <div v-for="dayOfWeek in [0, 1, 2, 3, 4, 5, 6]" :key="`${horse.id}-${dayOfWeek}`" class="space-y-3">
                <h4 class="font-medium text-text-dark">{{ dayName(dayOfWeek) }}</h4>

                <div class="pl-4 space-y-2 border-l-2 border-border-light">
                  <div v-if="getHorseWindowsForDay(horse.id, dayOfWeek).length === 0" class="text-light-brown italic">
                    Unavailable
                  </div>

                  <div v-for="slot in getHorseWindowsForDay(horse.id, dayOfWeek)" :key="slot.id" class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                    <div class="flex-1">
                      <span class="font-medium">{{ formatTime(slot.startTime) }} – {{ formatTime(slot.endTime) }}</span>
                      <span v-if="!slot.active" class="ml-2 text-xs text-light-brown">(disabled)</span>
                    </div>
                    <label class="flex items-center gap-2">
                      <input v-model="slot.active" type="checkbox" class="w-4 h-4" />
                      <span class="text-sm">Active</span>
                    </label>
                    <button @click="removeHorseWindow(slot.id)" class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                      Remove
                    </button>
                  </div>

                  <button @click="showAddHorseForm(horse.id, dayOfWeek)" class="text-sm text-accent-gold hover:underline font-medium">
                    + Add time window
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="adminStore.horses.length === 0" class="p-6 text-center text-light-brown">
            No horses configured yet.
          </div>
        </div>
      </div>

      <!-- Add Horse Window Form -->
      <div v-if="addHorseFormData.horseId" class="card card-padding bg-blue-50 border border-blue-200">
        <h3 class="font-bold mb-4">Add {{ getHorseName(addHorseFormData.horseId) }} – {{ dayName(addHorseFormData.dayOfWeek) }} Window</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="form-label">Start Time</label>
            <input v-model="newHorseWindow.startTime" type="time" class="form-input" />
          </div>
          <div>
            <label class="form-label">End Time</label>
            <input v-model="newHorseWindow.endTime" type="time" class="form-input" />
          </div>
          <div>
            <label class="flex items-center gap-2 mt-8">
              <input v-model="newHorseWindow.active" type="checkbox" />
              <span>Active</span>
            </label>
          </div>
        </div>
        <div class="flex gap-3">
          <button @click="addHorseWindow" class="btn-primary">Add Window</button>
          <button @click="addHorseFormData.horseId = ''" class="btn-secondary">Cancel</button>
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
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
import { ref, reactive, computed } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import { useNotification } from '@/composables/useNotification'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import { formatTime, validateNewWindow } from '@/utils/availabilityValidation'
import type { BlockedTime } from '@/types/models'

const adminStore = useAdminStore()
const { success, error } = useNotification()
const { confirm } = useConfirmDialog()

const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const dayName = (dayOfWeek: number) => days[dayOfWeek]

// Instructor form state
const addInstructorFormDay = ref<number | null>(null)
const newInstructorWindow = reactive({ startTime: '', endTime: '', active: true })

// Horse form state
const addHorseFormData = reactive({ horseId: '', dayOfWeek: 0 })
const newHorseWindow = reactive({ startTime: '', endTime: '', active: true })

// Blocked time form state
const blockForm = ref({
  horseId: '',
  date: '',
  startTime: '',
  endTime: '',
  reason: '',
})

const getInstructorWindowsForDay = (dayOfWeek: number) => {
  return adminStore.instructorAvailability.filter(a => a.dayOfWeek === dayOfWeek)
}

const getHorseWindowsForDay = (horseId: string, dayOfWeek: number) => {
  return adminStore.horseRecurringAvailability.filter(a => a.horseId === horseId && a.dayOfWeek === dayOfWeek)
}

const getHorseName = (horseId?: string) => {
  if (!horseId) return 'Instructor'
  const horse = adminStore.getHorse(horseId)
  return horse?.name || 'Unknown'
}

const showAddInstructorForm = (dayOfWeek: number) => {
  addInstructorFormDay.value = dayOfWeek
  newInstructorWindow.startTime = ''
  newInstructorWindow.endTime = ''
  newInstructorWindow.active = true
}

const addInstructorWindow = async () => {
  if (!newInstructorWindow.startTime || !newInstructorWindow.endTime) {
    error('Please fill in start and end times')
    return
  }

  if (addInstructorFormDay.value === null) return

  // Validate against existing windows for this day
  const existingWindows = getInstructorWindowsForDay(addInstructorFormDay.value)
  const validation = validateNewWindow(newInstructorWindow, existingWindows)

  if (!validation.valid) {
    error(validation.error || 'Invalid time window')
    return
  }

  const result = await adminStore.addInstructorAvailability({
    dayOfWeek: addInstructorFormDay.value,
    startTime: newInstructorWindow.startTime,
    endTime: newInstructorWindow.endTime,
    active: newInstructorWindow.active,
  })

  if (result) {
    success('Availability window added')
    addInstructorFormDay.value = null
  } else {
    error('Unable to add availability window')
  }
}

const removeInstructorWindow = (id: string) => {
  confirm({
    title: 'Remove Availability Window',
    message: 'Are you sure? This cannot be undone.',
    confirmText: 'Remove',
    cancelText: 'Cancel',
    isDangerous: true,
    onConfirm: async () => {
      const result = await adminStore.removeInstructorAvailability(id)
      if (result) {
        success('Availability window removed')
      } else {
        error('Unable to remove availability window')
      }
    },
  })
}

const showAddHorseForm = (horseId: string, dayOfWeek: number) => {
  addHorseFormData.horseId = horseId
  addHorseFormData.dayOfWeek = dayOfWeek
  newHorseWindow.startTime = ''
  newHorseWindow.endTime = ''
  newHorseWindow.active = true
}

const addHorseWindow = async () => {
  if (!newHorseWindow.startTime || !newHorseWindow.endTime) {
    error('Please fill in start and end times')
    return
  }

  if (!addHorseFormData.horseId) return

  // Validate against existing windows for this horse/day
  const existingWindows = getHorseWindowsForDay(addHorseFormData.horseId, addHorseFormData.dayOfWeek)
  const validation = validateNewWindow(newHorseWindow, existingWindows)

  if (!validation.valid) {
    error(validation.error || 'Invalid time window')
    return
  }

  const result = await adminStore.addHorseRecurringAvailability({
    horseId: addHorseFormData.horseId,
    dayOfWeek: addHorseFormData.dayOfWeek,
    startTime: newHorseWindow.startTime,
    endTime: newHorseWindow.endTime,
    active: newHorseWindow.active,
  })

  if (result) {
    success('Horse availability window added')
    addHorseFormData.horseId = ''
  } else {
    error('Unable to add horse availability window')
  }
}

const removeHorseWindow = (id: string) => {
  confirm({
    title: 'Remove Horse Availability Window',
    message: 'Are you sure? This cannot be undone.',
    confirmText: 'Remove',
    cancelText: 'Cancel',
    isDangerous: true,
    onConfirm: async () => {
      const result = await adminStore.removeHorseRecurringAvailability(id)
      if (result) {
        success('Horse availability window removed')
      } else {
        error('Unable to remove horse availability window')
      }
    },
  })
}

const addBlockedTime = async () => {
  if (!blockForm.value.date || !blockForm.value.startTime || !blockForm.value.endTime) {
    error('Please fill in date and times')
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
    success('Blocked time added')
  } else {
    error('Unable to add blocked time')
  }
}

const deleteBlockedTime = (blockId: string) => {
  confirm({
    title: 'Delete Blocked Time',
    message: 'Are you sure? This cannot be undone.',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    isDangerous: true,
    onConfirm: async () => {
      const result = await adminStore.deleteBlockedTime(blockId)
      if (result) {
        success('Blocked time removed')
      } else {
        error('Unable to delete blocked time')
      }
    },
  })
}
</script>
