<template>
  <PublicLayout>
    <section class="booking-banner">
      <div><h1>Book a Lesson</h1><p>Choose a lesson type, view available times, and<br class="hidden md:block" /> reserve your spot in just a few minutes.</p></div>
    </section>
    <section class="booking-content py-6 md:py-8">
      <div class="container-narrow max-w-4xl">

        <BookingWizard />
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import BookingWizard from '@/components/booking/BookingWizard.vue'
</script>

<style scoped>
.booking-banner { min-height: 280px; display: flex; align-items: flex-end; padding: 40px max(6%, calc((100% - 1100px) / 2)); color: white; background: linear-gradient(90deg, #0008, transparent), url('/images/Chestnut Horse at Golden Hour.png') center 45% / cover; }
.booking-banner h1 { font-weight: 400; font-size: 48px; margin-bottom: 10px; }
.booking-banner p { font-size: 19px; line-height: 1.4; margin: 0; }
.booking-content { background: #faf7f2; }
@media (max-width: 767px) { .booking-banner { min-height: 230px; padding: 28px 24px; } .booking-banner h1 { font-size: 36px; } .booking-banner p { font-size: 17px; } }
</style>
