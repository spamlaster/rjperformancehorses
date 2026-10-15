<template>
  <form class="card card-padding space-y-4" @submit.prevent="save">
    <h2 class="text-2xl font-serif font-bold">Add Session</h2>
    <p class="text-light-brown">Record an existing session in {{ timezone || 'the ranch’s local time' }}. Times outside public availability are allowed; conflicting bookings are blocked.</p>
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
        <option v-for="horse in eligibleHorses" :key="horse.id" :value="horse.id" :disabled="form.horse_ids.some((id, i) => i !== index && id === horse.id)">{{ horse.name }}</option>
      </select>
    </div>
    <p v-if="horseWarning" role="alert" class="text-red-700">{{ horseWarning }} <RouterLink to="/admin/horses" class="underline">Manage horse eligibility</RouterLink>.</p>
    <label class="flex gap-2 items-center"><input v-model="form.weekly" type="checkbox" /> Repeat weekly from this date</label>
    <p v-if="form.weekly" class="text-sm text-light-brown">Reserves this time every week until cancelled.</p>
    <div><label for="session-notes" class="form-label">Notes (optional)</label><textarea id="session-notes" v-model="form.notes" class="form-input" maxlength="5000"></textarea></div>
    <p class="text-sm text-light-brown">This records the reservation only. It does not sign a waiver for the rider.</p>
    <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
    <div class="flex gap-3">
      <button class="btn-primary" type="submit" :disabled="saving || !form.lesson_type_id || !!horseWarning">{{ saving ? 'Saving…' : 'Save Session' }}</button>
      <button class="btn-secondary" type="button" :disabled="saving" @click="$emit('cancel')">Cancel</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAdminStore } from '@/stores/adminStore'
import { fetchLessonTypes, fetchBusinessTimezone } from '@/services/apiService'
import { api } from '@/lib/api'
import type { LessonType } from '@/types/models'

const props = defineProps<{ riderId?: string }>()
const emit = defineEmits<{ saved: []; cancel: [] }>()
const store = useAdminStore()
const lessons = ref<LessonType[]>([])
const eligibleHorses = computed(() => store.horses.filter(h => h.active && !h.deletedAt && h.eligibleLessonTypeIds?.includes(form.lesson_type_id)))
const horseWarning = computed(() => {
  const lesson = lessons.value.find(l => l.id === form.lesson_type_id)
  if (!lesson || eligibleHorses.value.length >= lesson.requiredHorseCount) return ''
  return `${lesson.name} requires ${lesson.requiredHorseCount} eligible active horse(s), but only ${eligibleHorses.value.length} are configured. Update the horses’ eligible lessons before saving.`
})
const timezone = ref<string | null>(null)
const saving = ref(false)
const error = ref('')
const form = reactive({ lesson_type_id: '', customer_id: '', rider_ids: [props.riderId || ''], horse_ids: [''], date: '', time: '', weekly: false, notes: '' })

onMounted(async () => {
  try {
    const [types, zone] = await Promise.all([fetchLessonTypes(true), fetchBusinessTimezone()])
    lessons.value = types
    timezone.value = zone
    selectRider(0)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load session options.'
  }
})

function resetParticipants() {
  const lesson = lessons.value.find(l => l.id === form.lesson_type_id)
  form.rider_ids = Array.from({ length: lesson?.requiredRiderCount || 1 }, (_, i) => form.rider_ids[i] || '')
  form.horse_ids = Array.from({ length: lesson?.requiredHorseCount || 1 }, (_, i) => eligibleHorses.value.some(h => h.id === form.horse_ids[i]) ? form.horse_ids[i] : '')
}
function selectRider(index: number) {
  const rider = store.getRider(form.rider_ids[index])
  if (index === 0) form.customer_id = rider?.customerId || ''
}
async function save() {
  if (saving.value) return
  saving.value = true
  error.value = ''
  try {
    const result = await api('POST', '/admin/bookings', { ...form, customer_id: form.customer_id || null })
    if (!result.ok) throw new Error(result.error || 'Unable to save session.')
    emit('saved')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to save session.'
  } finally {
    saving.value = false
  }
}
</script>
