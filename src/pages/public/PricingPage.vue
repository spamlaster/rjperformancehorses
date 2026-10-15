<template>
  <PublicLayout>
    <section class="py-16 md:py-24">
      <div class="container-narrow space-y-10">
        <div class="text-center"><h1 class="text-4xl font-serif font-bold text-text-dark mb-4">Lesson Pricing</h1><p class="text-light-brown text-lg">Explore our current lessons and prices.</p></div>
        <LessonCatalog />
      </div>
    </section>
  </PublicLayout>
</template>
<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
import LessonCatalog from '@/components/booking/LessonCatalog.vue'
</script>
