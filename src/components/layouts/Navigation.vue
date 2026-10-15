<template>
  <nav class="ranch-navigation" aria-label="Main navigation">
    <div class="navigation-inner">
      <router-link to="/" class="ranch-logo" aria-label="RJ Performance Horses home">
        <span>RJ</span><small>Performance Horses</small>
      </router-link>
      <div class="desktop-links">
        <router-link v-for="link in links" :key="link.path" :to="link.path" :class="{ active: route.path === link.path }">{{ link.label }}</router-link>
      </div>
      <router-link to="/book" class="navigation-book">Book a Lesson</router-link>
      <button class="menu-toggle" @click="mobileMenuOpen = !mobileMenuOpen" :aria-expanded="mobileMenuOpen" aria-controls="mobile-navigation" :aria-label="mobileMenuOpen ? 'Close navigation' : 'Open navigation'">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" d="M5 7h14M5 12h14M5 17h14" /></svg>
      </button>
    </div>
    <div v-if="mobileMenuOpen" id="mobile-navigation" class="mobile-links">
      <router-link v-for="link in links" :key="link.path" :to="link.path" @click="mobileMenuOpen = false">{{ link.label }}</router-link>
      <router-link to="/book" @click="mobileMenuOpen = false">Book a Lesson</router-link>
    </div>
  </nav>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
const route = useRoute()
const mobileMenuOpen = ref(false)
const links = [{ path: '/', label: 'Home' }, { path: '/about', label: 'About' }, { path: '/lessons', label: 'Lessons' }, { path: '/pricing', label: 'Pricing' }, { path: '/faq', label: 'FAQ' }, { path: '/contact', label: 'Contact' }, { path: '/rider', label: 'Rider Account' }]
</script>
<style scoped>
.ranch-navigation { position: relative; z-index: 50; background: #faf7f2; color: #201713; }
.navigation-inner { max-width: 1400px; margin: auto; min-height: 82px; padding: 10px 6%; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
.ranch-logo { color: #382218; text-align: center; flex-shrink: 0; }
.ranch-logo span { display: block; font-size: 43px; line-height: .9; }
.ranch-logo small { display: block; text-transform: uppercase; letter-spacing: .18em; font-size: 10px; margin-top: 7px; }
.desktop-links { display: flex; gap: clamp(16px, 2.5vw, 32px); margin-left: auto; }
.desktop-links a { color: #16110e; font-size: 15px; padding: 10px 0; border-bottom: 1px solid transparent; }
.desktop-links a.active { border-color: #765036; }
.navigation-book { border-radius: 999px; padding: 10px 22px; color: white; background: linear-gradient(110deg, #89603b, #754c2e); border: 1px solid #603b25; font-size: 15px; white-space: nowrap; margin-left: 24px; }
.menu-toggle { display: none; background: transparent; border: 0; }
.mobile-links { padding: 16px 24px; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid #e6ddd3; }
.mobile-links a { color: #382218; }
@media (max-width: 900px) { .navigation-inner { min-height: 86px; padding: 14px 22px; } .ranch-logo span { font-size: 36px; } .ranch-logo small { font-size: 8px; } .desktop-links, .navigation-book { display: none; } .menu-toggle { display: block; } }
</style>
