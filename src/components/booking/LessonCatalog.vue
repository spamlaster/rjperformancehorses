<template>
  <div>
    <p v-if="loading" role="status" class="text-light-brown">Loading lessons…</p>
    <p v-else-if="error" role="alert" class="text-red-700">{{ error }} <button class="underline" @click="load">Try again</button></p>
    <p v-else-if="!lessons.length" class="text-light-brown text-center">Lesson details will be available soon. <RouterLink to="/contact" class="underline">Contact us to arrange a lesson</RouterLink>.</p>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <article v-for="lesson in lessons" :key="lesson.id" class="card card-padding space-y-4">
        <h2 class="font-serif text-2xl font-bold text-text-dark">{{ lesson.name }}</h2>
        <p v-if="lesson.description" class="text-light-brown">{{ lesson.description }}</p>
        <p class="text-2xl font-bold text-accent-gold">${{ lesson.price.toFixed(2) }}</p>
        <p class="text-sm text-light-brown">{{ lesson.durationMinutes }} minutes · {{ lesson.requiredRiderCount }} rider(s)</p>
        <RouterLink to="/book" class="btn-primary inline-block">Book Lesson</RouterLink>
      </article>
    </div>
  </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { fetchLessonTypes } from '@/services/apiService'
import type { LessonType } from '@/types/models'
const lessons = ref<LessonType[]>([])
const loading = ref(true)
const error = ref('')
async function load() {
  loading.value = true
  error.value = ''
  try { lessons.value = await fetchLessonTypes(true, true) }
  catch (err) { error.value = err instanceof Error ? err.message : 'Unable to load lessons.' }
  finally { loading.value = false }
}
onMounted(load)
</script>
