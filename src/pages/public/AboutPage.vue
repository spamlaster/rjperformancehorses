<template>
  <PublicLayout>
    <section class="py-16 md:py-24 border-b border-border-light">
      <div class="container-narrow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <img
            src="/images/rylie-profile.jpg"
            alt="Rylie Jensen standing beside a saddled horse"
            width="2048"
            height="2048"
            decoding="async"
            class="w-full h-auto rounded-lg shadow-md"
          />

          <!-- Content -->
          <div>
            <h1 class="text-4xl font-serif font-bold text-text-dark mb-6">Meet Rylie</h1>

            <p class="text-light-brown mb-4">
              Rylie Jensen has a passion for working with riders of all ages and experience levels.
            </p>

            <p class="text-light-brown mb-4">
              [Placeholder: Rylie's biography, background, and approach to teaching]
            </p>

            <p class="text-light-brown mb-6">
              With a focus on building confidence and creating positive learning experiences, Rylie creates a welcoming environment where riders feel safe to challenge themselves.
            </p>

            <div class="space-y-3 text-light-brown">
              <p><strong>Experience:</strong> [Years in business]</p>
              <p><strong>Specialties:</strong> Western riding, barrel racing, pole bending, horseman training</p>
              <p><strong>Certifications:</strong> [Placeholder for certifications]</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Philosophy Section -->
    <section class="py-16 md:py-24 bg-gray-100">
      <div class="container-narrow">
        <h2 class="text-3xl font-serif font-bold text-text-dark text-center mb-12">
          My Teaching Philosophy
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="card card-padding">
            <h3 class="font-serif text-xl font-bold mb-3 text-accent-gold">Safety First</h3>
            <p class="text-light-brown">
              All lessons prioritize rider and horse safety with proper instruction and appropriate equipment.
            </p>
          </div>
          <div class="card card-padding">
            <h3 class="font-serif text-xl font-bold mb-3 text-accent-gold">Individual Focus</h3>
            <p class="text-light-brown">
              Each rider's goals and learning style are unique. Lessons are customized accordingly.
            </p>
          </div>
          <div class="card card-padding">
            <h3 class="font-serif text-xl font-bold mb-3 text-accent-gold">Horse Wellness</h3>
            <p class="text-light-brown">
              Our horses are well-cared-for, trained, and matched to appropriate lesson types.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-16 md:py-24">
      <div class="container-narrow text-center">
        <h2 class="text-3xl font-serif font-bold text-text-dark mb-6">Start Your Journey Today</h2>
        <p class="text-lg text-light-brown mb-8">
          Ready to learn from Rylie? Book your first lesson now.
        </p>
        <router-link to="/book" class="btn-primary btn-large">
          Book a Lesson
        </router-link>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
</script>
