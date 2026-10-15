<template>
  <AdminLayout>
    <div class="space-y-6 max-w-4xl">
      <div>
        <h1 class="text-3xl font-serif font-bold text-text-dark">Availability</h1>
        <p class="text-light-brown mt-2">Set Rylie’s weekly hours, then each horse’s hours. Customers can book where both schedules overlap.</p>
        <p class="text-sm text-light-brown mt-2">Hours repeat every week in the ranch’s local time. Existing lessons and time off are removed from online booking automatically.</p>
      </div>
      <div class="card card-padding space-y-4">
        <div class="flex flex-wrap items-end gap-4">
          <div class="flex-1 min-w-[12rem]">
            <label for="schedule-resource" class="form-label">Whose hours?</label>
            <select id="schedule-resource" v-model="selectedHorse" :disabled="hoursSaving" class="form-select" @change="closeHoursForm">
              <option value="">Rylie</option>
              <option v-for="horse in adminStore.horses" :key="horse.id" :value="horse.id">{{ horse.name }}{{ horse.active ? '' : ' (inactive)' }}</option>
            </select>
          </div>
          <RouterLink to="/admin/horses" class="btn-secondary">Manage horses</RouterLink>
        </div>
        <p v-if="!adminStore.horses.length && !adminStore.isLoading" class="text-light-brown">Add a horse under Horses, then choose it here to set its hours.</p>
        <p v-if="selectedHorse && !adminStore.getHorse(selectedHorse)?.active" role="status" class="text-red-700">This horse is inactive. Activate it under Horses to use it for lessons.</p>
      </div>
      <p v-if="adminStore.isLoading" role="status">Loading hours…</p>
      <section v-else class="card card-padding space-y-5" :aria-label="`${resourceName} weekly hours`">
        <h2 class="text-xl font-bold">{{ resourceName }}’s weekly hours</h2>
        <div v-for="day in [1, 2, 3, 4, 5, 6, 0]" :key="day" class="border-t border-border-light pt-4 space-y-3" :data-day="day">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-bold">{{ dayName(day) }}</h3>
            <button type="button" class="text-sm text-dark-brown underline font-semibold" @click="openHoursForm(day)">+ Add hours</button>
          </div>
          <p v-if="!windowsForDay(day).length" class="text-sm text-light-brown">No hours set</p>
          <div v-for="slot in windowsForDay(day)" :key="slot.id" class="flex flex-wrap items-center gap-3 p-3 bg-gray-50 rounded">
            <span class="flex-1 font-medium">{{ formatTime(slot.startTime) }} – {{ formatTime(slot.endTime) }}</span>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="slot.active" :disabled="savingIds.has(slot.id)" :aria-label="`${dayName(day)} ${formatTime(slot.startTime)} enabled`"
                @change="selectedHorse ? toggleHorseActive(slot.id, $event) : toggleInstructorActive(slot.id, $event)" /> Enabled
            </label>
            <button type="button" class="text-sm text-red-700 underline" @click="selectedHorse ? removeHorseWindow(slot.id) : removeInstructorWindow(slot.id)">Remove</button>
          </div>
          <p v-if="selectedHorse && windowsForDay(day).some(w => w.active)" class="text-sm text-light-brown">
            {{ sharedHours(day) ? `Shared hours with Rylie: ${sharedHours(day)}. Lessons must fit within these hours.` : 'No shared hours with Rylie. Choose Rylie above and add hours that overlap.' }}
          </p>
          <form v-if="editingDay === day" class="p-4 rounded bg-gray-50 space-y-3" @submit.prevent="saveHours">
            <p class="font-semibold">Add {{ dayName(day) }} hours for {{ resourceName }}</p>
            <div class="grid grid-cols-2 gap-3">
              <div><label :for="`hours-start-${day}`" class="form-label">Start time</label><input :id="`hours-start-${day}`" v-model="currentWindow.startTime" type="time" required class="form-input" /></div>
              <div><label :for="`hours-end-${day}`" class="form-label">End time</label><input :id="`hours-end-${day}`" v-model="currentWindow.endTime" type="time" required class="form-input" /></div>
            </div>
            <div class="flex gap-3"><button type="submit" :disabled="hoursSaving" class="btn-primary">{{ hoursSaving ? 'Saving…' : 'Save hours' }}</button><button type="button" :disabled="hoursSaving" class="btn-secondary" @click="closeHoursForm">Cancel</button></div>
          </form>
        </div>
      </section>
      <details class="card card-padding">
        <summary class="font-bold text-xl cursor-pointer">Time off &amp; blocked times</summary>
        <p class="text-sm text-light-brown my-4">Block a date for a vacation, vet visit, or other time away.</p>
        <form class="space-y-4" @submit.prevent="addBlockedTime">
          <div class="grid sm:grid-cols-2 gap-4">
            <div><label for="block-resource" class="form-label">Who is unavailable?</label><select id="block-resource" v-model="blockForm.horseId" class="form-select"><option value="">Rylie (all lessons)</option><option v-for="horse in adminStore.horses" :key="horse.id" :value="horse.id">{{ horse.name }}</option></select></div>
            <div><label for="block-date" class="form-label">Date</label><input id="block-date" v-model="blockForm.date" type="date" required class="form-input" /></div>
            <div><label for="block-start" class="form-label">Start time</label><input id="block-start" v-model="blockForm.startTime" type="time" required class="form-input" /></div>
            <div><label for="block-end" class="form-label">End time</label><input id="block-end" v-model="blockForm.endTime" type="time" required class="form-input" /></div>
          </div>
          <div><label for="block-reason" class="form-label">Reason (optional)</label><input id="block-reason" v-model="blockForm.reason" class="form-input" /></div>
          <button class="btn-primary" type="submit">Block time</button>
        </form>
        <p v-if="!adminStore.blockedTimes.length" class="mt-4 text-light-brown">No time off scheduled.</p>
        <div v-for="block in adminStore.blockedTimes" :key="block.id" class="border-t border-border-light mt-4 pt-4 flex flex-wrap items-center justify-between gap-3">
          <div><p class="font-semibold">{{ block.horseId ? getHorseName(block.horseId) : 'Rylie' }} · {{ block.date }}</p><p>{{ formatTime(block.startTime) }} – {{ formatTime(block.endTime) }} {{ block.reason ? `· ${block.reason}` : '' }}</p></div>
          <button class="text-red-700 underline" @click="deleteBlockedTime(block.id)">Remove block</button>
        </div>
      </details>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, ref, reactive } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import { useNotification } from '@/composables/useNotification'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import { formatTime, validateNewWindow } from '@/utils/availabilityValidation'
import type { BlockedTime } from '@/types/models'

const adminStore = useAdminStore()
const route = useRoute()
const selectedHorse = ref(typeof route.query.horse === 'string' ? route.query.horse : '')
const resourceName = computed(() => selectedHorse.value ? getHorseName(selectedHorse.value) : 'Rylie')
const hoursSaving = ref(false)
const windowsForDay = (day: number) => selectedHorse.value ? getHorseWindowsForDay(selectedHorse.value, day) : getInstructorWindowsForDay(day)
const currentWindow = computed(() => selectedHorse.value ? newHorseWindow : newInstructorWindow)
const editingDay = computed(() => selectedHorse.value ? (addHorseFormData.horseId === selectedHorse.value ? addHorseFormData.dayOfWeek : null) : addInstructorFormDay.value)
const closeHoursForm = () => { addInstructorFormDay.value = null; addHorseFormData.horseId = '' }
const openHoursForm = (day: number) => {
  closeHoursForm()
  if (selectedHorse.value) showAddHorseForm(selectedHorse.value, day)
  else showAddInstructorForm(day)
}
const saveHours = async () => {
  if (hoursSaving.value) return
  hoursSaving.value = true
  try {
    if (selectedHorse.value) await addHorseWindow()
    else await addInstructorWindow()
  } finally { hoursSaving.value = false }
}
const sharedHours = (day: number) => {
  const ranges: string[] = []
  for (const horse of getHorseWindowsForDay(selectedHorse.value, day).filter(w => w.active)) {
    for (const instructor of getInstructorWindowsForDay(day).filter(w => w.active)) {
      const start = [horse.startTime, instructor.startTime].map(t => t.slice(0, 5)).sort()[1]
      const end = [horse.endTime, instructor.endTime].map(t => t.slice(0, 5)).sort()[0]
      if (start < end) ranges.push(`${formatTime(start)} – ${formatTime(end)}`)
    }
  }
  return [...new Set(ranges)].join(', ')
}
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

// Active toggles: the store only changes after the API confirms the save.
const savingIds = reactive(new Set<string>())

const saveActiveToggle = async (
  id: string,
  event: Event,
  save: (id: string, updates: { active: boolean }) => Promise<boolean>,
) => {
  const checkbox = event.target as HTMLInputElement
  const active = checkbox.checked
  savingIds.add(id)
  const saved = await save(id, { active })
  savingIds.delete(id)
  if (saved) {
    success(active ? 'Availability window enabled' : 'Availability window disabled')
  } else {
    checkbox.checked = !active
    error('Unable to save availability change')
  }
}

const toggleInstructorActive = (id: string, event: Event) =>
  saveActiveToggle(id, event, adminStore.updateInstructorAvailabilityLocal)

const toggleHorseActive = (id: string, event: Event) =>
  saveActiveToggle(id, event, adminStore.updateHorseAvailabilityLocal)

const getHorseName = (horseId?: string) => {
  if (!horseId) return 'Instructor'
  const horse = adminStore.getHorse(horseId)
  return horse?.name || 'Unknown'
}

const showAddInstructorForm = (dayOfWeek: number) => {
  addInstructorFormDay.value = dayOfWeek
  newInstructorWindow.startTime = '09:00'
  newInstructorWindow.endTime = '17:00'
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
  newHorseWindow.startTime = '09:00'
  newHorseWindow.endTime = '17:00'
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

  if (blockForm.value.startTime >= blockForm.value.endTime) {
    error('Start time must be before end time')
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
