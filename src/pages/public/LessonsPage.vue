<template>
  <PublicLayout>
    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-dark-brown to-text-dark text-white py-12 md:py-16">
      <div class="container-narrow text-center">
        <h1 class="text-4xl md:text-5xl font-serif font-bold mb-4">
          Lesson Programs
        </h1>
        <p class="text-lg text-gray-200">
          Choose the perfect lesson for your skill level and goals
        </p>
      </div>
    </section>

    <section class="py-16 md:py-24 bg-white">
      <div class="container-narrow">
        <!-- Regular Lessons -->
        <div class="mb-20">
          <h2 class="text-3xl md:text-4xl font-serif font-bold text-text-dark mb-2">Riding Lessons</h2>
          <p class="text-light-brown mb-8">One-on-one instruction tailored to your experience level</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card card-padding hover:shadow-lg transition-shadow">
              <h3 class="font-serif text-xl font-bold text-text-dark mb-3">30 Minute Private</h3>
              <p class="text-light-brown mb-4 text-sm leading-relaxed">
                Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.
              </p>
              <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-accent-gold mb-1">$45</p>
                <p class="text-sm text-light-brown">30 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary w-full text-center">Book Lesson</router-link>
            </div>

            <div class="card card-padding hover:shadow-lg transition-shadow border-2 border-accent-gold">
              <p class="text-xs font-bold text-accent-gold mb-2">POPULAR</p>
              <h3 class="font-serif text-xl font-bold text-text-dark mb-3">45 Minute Private</h3>
              <p class="text-light-brown mb-4 text-sm leading-relaxed">
                Build on fundamentals with canter and basic jumping techniques.
              </p>
              <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-accent-gold mb-1">$55</p>
                <p class="text-sm text-light-brown">45 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary w-full text-center">Book Lesson</router-link>
            </div>

            <div class="card card-padding hover:shadow-lg transition-shadow">
              <h3 class="font-serif text-xl font-bold text-text-dark mb-3">60 Minute Private</h3>
              <p class="text-light-brown mb-4 text-sm leading-relaxed">
                Advanced techniques and competition preparation for experienced riders.
              </p>
              <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-accent-gold mb-1">$65</p>
                <p class="text-sm text-light-brown">60 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary w-full text-center">Book Lesson</router-link>
            </div>
          </div>
        </div>

        <!-- Group Lessons -->
        <div class="mb-16">
          <h2 class="text-3xl font-serif font-bold text-text-dark mb-8">Joined/Sibling Lessons</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Group Lesson (60 min)</h3>
              <p class="text-light-brown mb-4 text-sm">
                Two riders learning together on separate horses. Perfect for siblings or friends.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$100</p>
                <p class="text-sm text-light-brown">60 minutes • 2 riders</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>

          </div>
        </div>

        <!-- Barrel & Pole -->
        <div>
          <h2 class="text-3xl font-serif font-bold text-text-dark mb-8">Barrel & Pole Training</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Barrel Racing (60 min)</h3>
              <p class="text-light-brown mb-4 text-sm">
                Learn proper barrel racing techniques and speed control.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$75</p>
                <p class="text-sm text-light-brown">60 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>

            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Barrel Racing (90 min)</h3>
              <p class="text-light-brown mb-4 text-sm">
                Extended session for deeper practice and refinement.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$100</p>
                <p class="text-sm text-light-brown">90 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>

          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
</script>
