<template>
  <PublicLayout>
    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-cream to-white py-16 md:py-24">
      <div class="container-narrow text-center">
        <h1 class="text-4xl md:text-5xl font-serif font-bold text-text-dark mb-4">
          RJ Performance Horses
        </h1>
        <p class="text-xl md:text-2xl text-light-brown font-serif italic mb-6">
          Confidence in the Saddle
        </p>
        <p class="text-lg text-light-brown mb-8 max-w-2xl mx-auto">
          Riding Lessons • Barrel & Pole Training • Horsemanship
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link to="/book" class="btn-primary btn-large">
            Book a Lesson
          </router-link>
          <router-link to="/lessons" class="btn-secondary btn-large">
            Learn More
          </router-link>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 md:py-24 border-b border-border-light">
      <div class="container-narrow">
        <h2 class="text-3xl font-serif font-bold text-text-dark text-center mb-12">
          Why Choose RJ Performance?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="card card-padding text-center">
            <div class="text-4xl mb-4">🐴</div>
            <h3 class="font-serif text-xl font-bold mb-3">Professional Training</h3>
            <p class="text-light-brown">
              Expert instruction tailored to your experience level and goals.
            </p>
          </div>
          <div class="card card-padding text-center">
            <div class="text-4xl mb-4">🏆</div>
            <h3 class="font-serif text-xl font-bold mb-3">Quality Horses</h3>
            <p class="text-light-brown">
              Well-trained, friendly horses selected for each lesson type.
            </p>
          </div>
          <div class="card card-padding text-center">
            <div class="text-4xl mb-4">🎯</div>
            <h3 class="font-serif text-xl font-bold mb-3">Flexible Scheduling</h3>
            <p class="text-light-brown">
              Book lessons online with availability that works for your schedule.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Lessons Section -->
    <section class="py-16 md:py-24 border-b border-border-light">
      <div class="container-narrow">
        <h2 class="text-3xl font-serif font-bold text-text-dark text-center mb-12">
          Our Lessons & Training
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
          <div class="card card-padding">
            <div class="bg-accent-gold rounded-lg h-40 mb-4 flex items-center justify-center text-white text-3xl">
              🏇
            </div>
            <h3 class="font-serif text-xl font-bold mb-2">Riding Lessons</h3>
            <p class="text-light-brown mb-4">
              Private lessons for all experience levels, from beginner to advanced.
            </p>
            <router-link to="/lessons" class="text-accent-gold font-medium hover:text-warm-gold">
              View Options →
            </router-link>
          </div>
          <div class="card card-padding">
            <div class="bg-accent-gold rounded-lg h-40 mb-4 flex items-center justify-center text-white text-3xl">
              👥
            </div>
            <h3 class="font-serif text-xl font-bold mb-2">Joined/Sibling Lessons</h3>
            <p class="text-light-brown mb-4">
              Learn together with a friend or family member on separate horses.
            </p>
            <router-link to="/lessons" class="text-accent-gold font-medium hover:text-warm-gold">
              View Options →
            </router-link>
          </div>
          <div class="card card-padding">
            <div class="bg-accent-gold rounded-lg h-40 mb-4 flex items-center justify-center text-white text-3xl">
              🔥
            </div>
            <h3 class="font-serif text-xl font-bold mb-2">Barrel & Pole Training</h3>
            <p class="text-light-brown mb-4">
              Specialized training for barrel racing and pole bending techniques.
            </p>
            <router-link to="/lessons" class="text-accent-gold font-medium hover:text-warm-gold">
              View Options →
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-dark-brown text-cream py-16 md:py-24">
      <div class="container-narrow text-center">
        <h2 class="text-3xl font-serif font-bold mb-4">Ready to Ride?</h2>
        <p class="text-lg mb-8 text-gray-200">
          Check out our current availability and book your lesson online.
        </p>
        <router-link to="/book" class="btn-primary inline-block btn-large">
          Book a Lesson Today
        </router-link>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
</script>
