<template>
  <PublicLayout>
    <section class="py-16 md:py-24">
      <div class="container-narrow">
        <h1 class="text-4xl font-serif font-bold text-text-dark text-center mb-4">
          Transparent Pricing
        </h1>
        <p class="text-center text-light-brown text-lg mb-12">
          Quality instruction at fair prices. See all our lesson options below.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
          <!-- Private Lessons -->
          <div class="card card-padding">
            <h3 class="font-serif text-2xl font-bold text-accent-gold mb-6">Private Lessons</h3>
            <div class="space-y-4">
              <div class="border-b border-border-light pb-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-bold">Walk & Trot</h4>
                  <span class="text-accent-gold font-bold text-lg">$45</span>
                </div>
                <p class="text-sm text-light-brown">30 minutes</p>
              </div>
              <div class="border-b border-border-light pb-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-bold">Intermediate</h4>
                  <span class="text-accent-gold font-bold text-lg">$55</span>
                </div>
                <p class="text-sm text-light-brown">45 minutes</p>
              </div>
              <div class="pb-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-bold">Advanced</h4>
                  <span class="text-accent-gold font-bold text-lg">$65</span>
                </div>
                <p class="text-sm text-light-brown">60 minutes</p>
              </div>
            </div>
          </div>

          <!-- Group & Barrel -->
          <div class="card card-padding">
            <h3 class="font-serif text-2xl font-bold text-accent-gold mb-6">Group & Specialty</h3>
            <div class="space-y-4">
              <div class="border-b border-border-light pb-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-bold">Sibling (60 min)</h4>
                  <span class="text-accent-gold font-bold text-lg">$100</span>
                </div>
                <p class="text-sm text-light-brown">2 riders</p>
              </div>
              <div class="border-b border-border-light pb-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-bold">Barrel Racing (60 min)</h4>
                  <span class="text-accent-gold font-bold text-lg">$75</span>
                </div>
                <p class="text-sm text-light-brown">1 rider</p>
              </div>
            </div>
          </div>
        </div>

        <!-- FAQ -->
        <div class="bg-gray-100 card card-padding">
          <h3 class="font-serif text-2xl font-bold text-text-dark mb-6">Pricing Information</h3>
          <div class="space-y-4 text-light-brown">
            <p><strong>Payment:</strong> Accepted at booking</p>
            <p><strong>Cancellations:</strong> 24-hour notice required for refund</p>
            <p><strong>Discounts:</strong> Available for multi-lesson packages</p>
            <p><strong>Group Rates:</strong> Contact for custom group pricing</p>
          </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-12">
          <router-link to="/book" class="btn-primary btn-large">
            Book Your First Lesson
          </router-link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
</script>
