<template>
  <PublicLayout>
    <section class="py-16 md:py-24">
      <div class="container-narrow">
        <h1 class="text-4xl font-serif font-bold text-text-dark text-center mb-12">
          Lesson Programs
        </h1>

        <!-- Regular Lessons -->
        <div class="mb-16">
          <h2 class="text-3xl font-serif font-bold text-text-dark mb-8">Riding Lessons</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Walk & Trot</h3>
              <p class="text-light-brown mb-4 text-sm">
                Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$45</p>
                <p class="text-sm text-light-brown">30 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>

            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Intermediate Riding</h3>
              <p class="text-light-brown mb-4 text-sm">
                Build on fundamentals with canter and basic jumping techniques.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$55</p>
                <p class="text-sm text-light-brown">45 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>

            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Advanced Horsemanship</h3>
              <p class="text-light-brown mb-4 text-sm">
                Advanced techniques and competition preparation for experienced riders.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$65</p>
                <p class="text-sm text-light-brown">60 minutes • 1 rider</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
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

            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Group Lesson (90 min)</h3>
              <p class="text-light-brown mb-4 text-sm">
                Extended group lesson with more time for instruction and practice.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$120</p>
                <p class="text-sm text-light-brown">90 minutes • 2 riders</p>
              </div>
              <router-link to="/book" class="btn-primary text-sm">Book Lesson</router-link>
            </div>
          </div>
        </div>

        <!-- Barrel & Pole -->
        <div>
          <h2 class="text-3xl font-serif font-bold text-text-dark mb-8">Barrel & Pole Training</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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

            <div class="card card-padding">
              <h3 class="font-serif text-xl font-bold mb-2">Weekly Package</h3>
              <p class="text-light-brown mb-4 text-sm">
                Two 60-minute barrel racing lessons per week for committed riders.
              </p>
              <div class="mb-4">
                <p class="text-2xl font-bold text-accent-gold">$130<span class="text-sm">/week</span></p>
                <p class="text-sm text-light-brown">2x 60 min • 1 rider</p>
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
