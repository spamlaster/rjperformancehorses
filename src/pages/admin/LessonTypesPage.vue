<template>
  <AdminLayout>
    <div class="max-w-4xl space-y-6">
      <div class="flex flex-wrap justify-between items-center gap-3">
        <h1 class="text-3xl font-serif font-bold">Lesson Types</h1>
        <button class="btn-primary" @click="openForm()">+ Add Lesson Type</button>
      </div>
      <p class="text-light-brown">Create the lessons you offer. Active lesson types appear in bookings and on the online booking form.</p>
      <p v-if="notice" role="status">{{ notice }}</p>
      <p v-if="loading" role="status">Loading lesson types…</p>
      <p v-if="error" role="alert" class="text-red-700">{{ error }} <button v-if="!showForm" class="underline" @click="load">Try again</button></p>
      <form v-if="showForm" class="card card-padding space-y-4" @submit.prevent="save">
        <h2 class="text-xl font-bold">{{ form.id ? 'Edit Lesson Type' : 'Add Lesson Type' }}</h2>
        <div v-if="!form.id" class="rounded bg-gray-50 p-4 space-y-2">
          <label for="lesson-template" class="form-label">Start with a template</label>
          <select id="lesson-template" v-model="selectedTemplate" class="form-select" @change="applyTemplate">
            <option value="">Custom lesson — start blank</option>
            <option v-for="template in lessonTemplates" :key="template.key" :value="template.key">{{ template.values.name }} · ${{ template.values.price.toFixed(2) }}</option>
          </select>
          <p class="text-sm text-light-brown">Standard lessons come with a description, duration, price, and rider/horse counts. Change any value below, then save to add the lesson.</p>
        </div>
        <div><label for="lesson-name" class="form-label">Lesson name</label><input id="lesson-name" v-model="form.name" required maxlength="100" class="form-input" /></div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label for="lesson-duration" class="form-label">Duration (minutes)</label><input id="lesson-duration" v-model.number="form.durationMinutes" type="number" min="1" max="1440" required class="form-input" /></div>
          <div><label for="lesson-price" class="form-label">Price ($)</label><input id="lesson-price" v-model.number="form.price" type="number" min="0" max="999999.99" step="0.01" required class="form-input" /></div>
          <div><label for="lesson-riders" class="form-label">Number of riders</label><input id="lesson-riders" v-model.number="form.requiredRiderCount" type="number" min="1" max="20" required class="form-input" /></div>
          <div><label for="lesson-horses" class="form-label">Number of horses</label><input id="lesson-horses" v-model.number="form.requiredHorseCount" type="number" min="1" max="20" required class="form-input" /></div>
        </div>
        <div><label for="lesson-description" class="form-label">Description (optional)</label><textarea id="lesson-description" v-model="form.description" maxlength="5000" class="form-input"></textarea></div>
        <label class="flex items-center gap-2"><input v-model="form.active" type="checkbox" /> Active — allow new bookings</label>
        <p class="text-sm text-light-brown">Deactivate a lesson to stop new bookings. Existing bookings stay on the schedule.</p>
        <div class="flex gap-3"><button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save Lesson Type' }}</button><button type="button" class="btn-secondary" :disabled="saving" @click="showForm = false">Cancel</button></div>
      </form>
      <div v-if="!loading && !lessons.length && !showForm" class="card card-padding">
        <h2 class="text-xl font-bold mb-2">Add your first lesson type</h2>
        <p class="text-light-brown">Choose a standard lesson template or create a custom lesson. Once saved, it will be available under Bookings.</p>
      </div>
      <div v-for="lesson in lessons" :key="lesson.id" class="card card-padding flex flex-wrap justify-between items-start gap-4">
        <div><h2 class="text-xl font-bold">{{ lesson.name }}</h2><p>{{ lesson.durationMinutes }} minutes · ${{ lesson.price.toFixed(2) }} · {{ lesson.requiredRiderCount }} rider(s) · {{ lesson.requiredHorseCount }} horse(s)</p><p class="text-sm text-light-brown">{{ lesson.active ? 'Active' : 'Inactive' }}</p><p v-if="lesson.description" class="text-light-brown mt-2">{{ lesson.description }}</p></div>
        <button class="btn-secondary" :aria-label="`Edit ${lesson.name}`" @click="openForm(lesson)">Edit</button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { fetchLessonTypes, saveLessonType } from '@/services/apiService'
import { useAdminStore } from '@/stores/adminStore'
import { lessonTemplates } from '@/data/lessonTemplates'
import type { LessonType } from '@/types/models'

const store = useAdminStore()
const lessons = ref<LessonType[]>([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const notice = ref('')
const showForm = ref(false)
const defaults = (): Partial<LessonType> => ({ name: '', description: '', durationMinutes: 60, price: 0, requiredRiderCount: 1, requiredHorseCount: 1, maxGroupSize: 1, difficultyLevel: 'beginner', active: true })
const form = ref(defaults())
const selectedTemplate = ref('')
function applyTemplate() {
  const template = lessonTemplates.find(t => t.key === selectedTemplate.value)
  form.value = template ? { ...defaults(), ...template.values } : defaults()
}
async function load() {
  loading.value = true
  error.value = ''
  try {
    lessons.value = await fetchLessonTypes(false, true)
    store.lessonTypes = lessons.value
  } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to load lesson types.' }
  finally { loading.value = false }
}
function openForm(lesson?: LessonType) {
  selectedTemplate.value = ''
  form.value = lesson ? { ...lesson } : defaults()
  error.value = ''
  notice.value = ''
  showForm.value = true
}
async function save() {
  if (saving.value) return
  saving.value = true
  error.value = ''
  try {
    const saved = await saveLessonType({ ...form.value, maxGroupSize: form.value.requiredRiderCount })
    lessons.value = [...lessons.value.filter(l => l.id !== saved.id), saved]
    store.lessonTypes = lessons.value
    showForm.value = false
    notice.value = 'Lesson type saved.'
  } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to save lesson type.' }
  finally { saving.value = false }
}
onMounted(load)
</script>
