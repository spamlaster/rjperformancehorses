<template>
  <section class="card card-padding space-y-4" aria-label="Setup checklist">
    <h2 class="text-2xl font-serif font-bold">Set up your ranch</h2>
    <p class="text-light-brown">Start with your lesson types, then add horses and weekly hours. Add a waiver before accepting online bookings.</p>
    <div v-for="step in steps" :key="step.label" class="flex flex-wrap items-center justify-between gap-3 border-t border-border-light pt-3">
      <p><span class="font-semibold">{{ step.done ? '✓' : '○' }} {{ step.label }}</span><span class="block text-sm text-light-brown">{{ step.detail }}</span></p>
      <RouterLink :to="step.path" class="underline text-dark-brown font-semibold">{{ step.done ? 'Manage' : 'Set up' }}</RouterLink>
    </div>
    <p class="text-sm text-light-brown">Online booking times require overlapping Rylie and horse hours. You can enter an admin session outside those hours.</p>
  </section>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import type { SetupStatus } from '@/services/setupService'
const props = defineProps<{ status: SetupStatus }>()
const steps = computed(() => [
  { label: 'Lesson types', done: props.status.lesson_count > 0, detail: `${props.status.lesson_count} active lesson type(s)`, path: '/admin/lesson-types' },
  { label: 'Horses', done: props.status.horse_count > 0, detail: `${props.status.horse_count} active horse(s)`, path: '/admin/horses' },
  { label: 'Weekly hours', done: props.status.instructor_hours && props.status.horse_hours, detail: 'Set Rylie’s hours and each horse’s hours.', path: '/admin/availability' },
  { label: 'Booking waiver', done: !!props.status.waiver, detail: props.status.waiver ? `Version ${props.status.waiver.version} published` : 'Add the waiver riders will sign online.', path: '/admin/setup#waiver' },
])
</script>
