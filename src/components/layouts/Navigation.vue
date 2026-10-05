<template>
  <nav class="bg-white border-b border-border-light sticky top-0 z-50 shadow-sm">
    <div class="container-narrow flex justify-between items-center h-16">
      <!-- Logo -->
      <router-link to="/" class="flex items-center gap-2 text-text-dark hover:text-accent-gold">
        <div class="w-8 h-8 bg-accent-gold rounded-full"></div>
        <span class="font-serif text-lg font-bold hidden sm:inline">RJ Performance</span>
      </router-link>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center gap-8">
        <router-link
          to="/lessons"
          class="text-text-dark hover:text-accent-gold transition-colors"
        >
          Lessons
        </router-link>
        <router-link
          to="/pricing"
          class="text-text-dark hover:text-accent-gold transition-colors"
        >
          Pricing
        </router-link>
        <router-link
          to="/about"
          class="text-text-dark hover:text-accent-gold transition-colors"
        >
          About
        </router-link>
        <router-link
          to="/faq"
          class="text-text-dark hover:text-accent-gold transition-colors"
        >
          FAQ
        </router-link>
        <router-link
          to="/contact"
          class="text-text-dark hover:text-accent-gold transition-colors"
        >
          Contact
        </router-link>
      </div>

      <!-- CTA Button -->
      <div class="flex items-center gap-4">
        <router-link
          to="/book"
          class="btn-primary hidden sm:inline-block"
        >
          Book a Lesson
        </router-link>

        <!-- Mobile Menu Button -->
        <button
          class="md:hidden p-2 text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-border-light">
      <div class="container-narrow py-4 flex flex-col gap-4">
        <router-link
          to="/lessons"
          class="text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = false"
        >
          Lessons
        </router-link>
        <router-link
          to="/pricing"
          class="text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = false"
        >
          Pricing
        </router-link>
        <router-link
          to="/about"
          class="text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = false"
        >
          About
        </router-link>
        <router-link
          to="/faq"
          class="text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = false"
        >
          FAQ
        </router-link>
        <router-link
          to="/contact"
          class="text-text-dark hover:text-accent-gold"
          @click="mobileMenuOpen = false"
        >
          Contact
        </router-link>
        <router-link
          to="/book"
          class="btn-primary block text-center"
          @click="mobileMenuOpen = false"
        >
          Book a Lesson
        </router-link>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const mobileMenuOpen = ref(false)
</script>
