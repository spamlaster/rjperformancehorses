<template>
  <PublicLayout>
    <section class="bg-gradient-to-b from-dark-brown to-text-dark text-white py-12 md:py-16">
      <div class="container-narrow text-center"><h1 class="text-4xl md:text-5xl font-serif font-bold mb-4">Lesson Programs</h1><p class="text-lg text-gray-200">Find a lesson for your experience level and goals.</p></div>
    </section>
    <section class="py-16 md:py-24"><div class="container-narrow"><LessonCatalog /></div></section>
  </PublicLayout>
</template>
<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import LessonCatalog from '@/components/booking/LessonCatalog.vue'
</script>
