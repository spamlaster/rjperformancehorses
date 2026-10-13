<template>
  <div class="public-site min-h-screen flex flex-col bg-cream">
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

<style>
.public-site { font-family: 'Times New Roman', Georgia, serif; font-weight: 400; color: #211b17; background: #faf7f2; }
.public-site button, .public-site input, .public-site select, .public-site textarea { font-family: inherit; }
.public-site .btn-primary { background: #7b5233; color: white; border: 1px solid #694226; }
.public-site .btn-primary:hover { background: #603b25; }
</style>
