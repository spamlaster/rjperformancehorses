<template>
  <form class="card card-padding space-y-4" novalidate @submit.prevent="save">
    <h2 class="text-2xl font-serif font-bold">Add Session</h2>
    <p class="text-light-brown">Add a lesson in {{ timezone || 'the ranch’s local time' }}. Choose any active horse. Weekly hours control online booking; you can enter a session at another time here.</p>
    <p v-if="!optionsLoaded && !error" role="status" class="text-light-brown">Loading session options…</p>
    <p v-if="optionsLoaded && !lessons.length" class="text-light-brown">Add your first lesson type before creating a session. <RouterLink to="/admin/lesson-types" class="underline">Add lesson types</RouterLink>.</p>
    <div>
      <label for="session-lesson" class="form-label">Lesson</label>
      <select id="session-lesson" v-model="form.lesson_type_id" class="form-select" required @change="resetParticipants">
        <option value="" disabled>Select lesson</option>
        <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">{{ lesson.name }} · {{ lesson.durationMinutes }} minutes</option>
      </select>
    </div>
    <div v-for="(_, index) in form.rider_ids" :key="index">
      <label :for="`session-rider-${index}`" class="form-label">Rider {{ form.rider_ids.length > 1 ? index + 1 : '' }}</label>
      <select :id="`session-rider-${index}`" v-model="form.rider_ids[index]" class="form-select" required @change="selectRider(index)">
        <option value="" disabled>Select rider</option>
        <option v-for="rider in store.riders" :key="rider.id" :value="rider.id" :disabled="form.rider_ids.some((id, i) => i !== index && id === rider.id)">{{ rider.firstName }} {{ rider.lastName }}</option>
      </select>
    </div>
    <div>
      <label for="session-contact" class="form-label">Booking Contact</label>
      <select id="session-contact" v-model="form.customer_id" class="form-select">
        <option value="">Use selected rider’s contact / email</option>
        <option v-for="customer in store.customers" :key="customer.id" :value="customer.id">{{ customer.firstName }} {{ customer.lastName }} · {{ customer.email }}</option>
      </select>
      <p class="text-sm text-light-brown">The rider’s existing contact is selected automatically when available.</p>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label for="session-date" class="form-label">Date / First Session</label><input id="session-date" v-model="form.date" class="form-input" type="date" required /></div>
      <div><label for="session-time" class="form-label">Start Time</label><input id="session-time" v-model="form.time" class="form-input" type="time" required /></div>
    </div>
    <div v-for="(_, index) in form.horse_ids" :key="`horse-${index}`">
      <label :for="`session-horse-${index}`" class="form-label">Horse {{ form.horse_ids.length > 1 ? index + 1 : '' }}</label>
      <select :id="`session-horse-${index}`" v-model="form.horse_ids[index]" class="form-select" required>
        <option value="" disabled>Select horse</option>
        <option v-for="horse in activeHorses" :key="horse.id" :value="horse.id" :disabled="form.horse_ids.some((id, i) => i !== index && id === horse.id)">{{ horse.name }}</option>
      </select>
    </div>
    <p v-if="horseWarning" role="alert" class="text-red-700">{{ horseWarning }} <RouterLink to="/admin/horses" class="underline">Add or activate a horse</RouterLink>.</p>
    <label class="flex gap-2 items-center"><input v-model="form.weekly" type="checkbox" /> Repeat weekly from this date</label>
    <p v-if="form.weekly" class="text-sm text-light-brown">Reserves this time every week until cancelled.</p>
    <div><label for="session-notes" class="form-label">Notes (optional)</label><textarea id="session-notes" v-model="form.notes" class="form-input" maxlength="5000"></textarea></div>
    <p class="text-sm text-light-brown">This records the reservation only. It does not sign a waiver for the rider.</p>
    <div v-if="error" ref="errorPanel" role="alert" tabindex="-1" class="rounded border border-red-300 bg-red-50 p-3 text-red-700">
      <p>{{ error }}</p>
      <button v-if="!optionsLoaded" type="button" class="underline mt-2" @click="loadOptions">Try loading options again</button>
    </div>
    <div class="flex gap-3">
      <button class="btn-primary" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save Session' }}</button>
      <button class="btn-secondary" type="button" :disabled="saving" @click="$emit('cancel')">Cancel</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useAdminStore } from '@/stores/adminStore'
import { fetchLessonTypes, fetchBusinessTimezone } from '@/services/apiService'
import { api } from '@/lib/api'
import type { LessonType } from '@/types/models'

const props = defineProps<{ riderId?: string }>()
const emit = defineEmits<{ saved: []; cancel: [] }>()
const store = useAdminStore()
const lessons = ref<LessonType[]>([])
const optionsLoaded = ref(false)
const activeHorses = computed(() => store.horses.filter(h => h.active && !h.deletedAt))
const horseWarning = computed(() => {
  const lesson = lessons.value.find(l => l.id === form.lesson_type_id)
  if (!lesson || activeHorses.value.length >= lesson.requiredHorseCount) return ''
  return `This lesson needs ${lesson.requiredHorseCount} active horse(s). You currently have ${activeHorses.value.length}.`
})
const timezone = ref<string | null>(null)
const saving = ref(false)
const error = ref('')
const errorPanel = ref<HTMLElement | null>(null)
const form = reactive({ lesson_type_id: '', customer_id: '', rider_ids: [props.riderId || ''], horse_ids: [''], date: '', time: '', weekly: false, notes: '' })

async function loadOptions() {
  error.value = ''
  optionsLoaded.value = false
  try {
    const [types, zone] = await Promise.all([fetchLessonTypes(true, true), fetchBusinessTimezone()])
    lessons.value = types
    optionsLoaded.value = true
    timezone.value = zone
    selectRider(0)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load session options.'
  }
}
onMounted(loadOptions)

function resetParticipants() {
  const lesson = lessons.value.find(l => l.id === form.lesson_type_id)
  form.rider_ids = Array.from({ length: lesson?.requiredRiderCount || 1 }, (_, i) => form.rider_ids[i] || '')
  form.horse_ids = Array.from({ length: lesson?.requiredHorseCount || 1 }, (_, i) => activeHorses.value.some(h => h.id === form.horse_ids[i]) ? form.horse_ids[i] : (i === 0 && activeHorses.value.length === 1 ? activeHorses.value[0].id : ''))
}
watch(activeHorses, () => {
  if (form.lesson_type_id) resetParticipants()
})

function selectRider(index: number) {
  const rider = store.getRider(form.rider_ids[index])
  if (index === 0) form.customer_id = rider?.customerId || ''
}
function validateSession(): string {
  if (!optionsLoaded.value) return 'Session options are not loaded. Try loading them again before saving.'
  if (!form.lesson_type_id) return 'Select a lesson before saving this session.'
  const lesson = lessons.value.find(l => l.id === form.lesson_type_id)
  if (!lesson) return 'This lesson is no longer available. Choose another lesson.'
  const missingRider = form.rider_ids.findIndex(id => !id)
  if (missingRider !== -1) return `Select ${form.rider_ids.length > 1 ? `rider ${missingRider + 1}` : 'a rider'} before saving this session.`
  if (!form.date) return 'Choose a date for this session.'
  if (!form.time) return 'Choose a start time for this session.'
  if (horseWarning.value) return horseWarning.value
  const missingHorse = form.horse_ids.findIndex(id => !id)
  if (missingHorse !== -1) return `Select ${form.horse_ids.length > 1 ? `horse ${missingHorse + 1}` : 'a horse'} before saving this session.`
  if (new Set(form.rider_ids).size !== lesson.requiredRiderCount) return `Choose ${lesson.requiredRiderCount} different rider(s) for this lesson.`
  if (new Set(form.horse_ids).size !== lesson.requiredHorseCount) return `Choose ${lesson.requiredHorseCount} different horse(s) for this lesson.`
  return ''
}
async function showError(message: string) {
  error.value = message
  await nextTick()
  errorPanel.value?.focus()
}
async function save() {
  if (saving.value) return
  const validationError = validateSession()
  if (validationError) {
    await showError(validationError)
    return
  }
  saving.value = true
  error.value = ''
  try {
    const result = await api('POST', '/admin/bookings', { ...form, customer_id: form.customer_id || null })
    if (!result.ok) throw new Error(result.error || 'Unable to save session.')
    emit('saved')
  } catch (err) {
    await showError(err instanceof Error ? err.message : 'Unable to save session.')
  } finally {
    saving.value = false
  }
}
</script>
