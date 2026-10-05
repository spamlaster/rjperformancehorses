<template>
  <div class="min-h-screen flex flex-col bg-cream">
    <Navigation />
    <main class="flex-1">
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup lang="ts">
import Navigation from './Navigation.vue'
import Footer from './Footer.vue'
</script>
