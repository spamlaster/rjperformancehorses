<template>
  <PublicLayout>
    <section class="py-12 md:py-16 bg-gray-50">
      <div class="container-narrow max-w-2xl">
        <h1 class="text-3xl font-serif font-bold text-text-dark text-center mb-2">
          Book Your Lesson
        </h1>
        <p class="text-center text-light-brown mb-8">
          Easy online booking. Select a lesson, date, and provide your information.
        </p>

        <BookingWizard />
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import BookingWizard from '@/components/booking/BookingWizard.vue'
</script>
