<template>
  <PublicLayout>
    <!-- Hero Section with Background Image -->
    <section class="relative h-96 md:h-[500px] bg-gray-900 overflow-hidden flex items-center justify-center">
      <!-- Background image placeholder -->
      <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-black/20"></div>
      <div class="absolute inset-0 bg-cover bg-center" style="background-image: linear-gradient(135deg, rgba(92, 64, 51, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%);"></div>

      <div class="relative z-10 container-narrow text-center text-white">
        <h1 class="text-5xl md:text-6xl font-serif font-bold mb-2">
          RJ
        </h1>
        <p class="text-2xl md:text-3xl font-serif tracking-widest mb-4">
          PERFORMANCE HORSES
        </p>
        <p class="text-xl md:text-2xl italic font-serif mb-8 text-cream">
          Confidence in the Saddle
        </p>
        <p class="text-lg md:text-xl mb-12 tracking-wide max-w-2xl mx-auto">
          Riding Lessons • Barrel & Pole Training • Horsemanship
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link to="/book" class="btn-primary btn-large">
            Book a Lesson
          </router-link>
          <router-link to="/lessons" class="bg-transparent border-2 border-white text-white hover:bg-white hover:text-text-dark font-medium py-3 px-8 rounded-md transition-all">
            Learn More
          </router-link>
        </div>
      </div>
    </section>

    <!-- About Rylie Section -->
    <section class="py-16 md:py-24 bg-cream">
      <div class="container-narrow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <!-- Image -->
          <div class="order-2 md:order-1">
            <div class="bg-gradient-to-br from-accent-gold to-warm-gold rounded-lg h-96 md:h-full flex items-center justify-center text-white text-6xl shadow-lg">
              📸
            </div>
          </div>

          <!-- Content -->
          <div class="order-1 md:order-2">
            <h2 class="text-4xl md:text-5xl font-serif font-bold text-text-dark mb-6">
              About Rylie
            </h2>
            <p class="text-lg text-light-brown mb-4 leading-relaxed">
              Hi, I'm Rylie! I'm passionate about helping riders of all levels build confidence, learn solid horsemanship, and have fun in the process.
            </p>
            <p class="text-lg text-light-brown mb-6 leading-relaxed">
              Whether you're just getting started or looking to improve your barrel and pole skills, my goal is to create a positive, supportive, and encouraging environment for every rider.
            </p>
            <router-link to="/about" class="btn-primary">
              Meet Rylie
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Lessons & Training Section -->
    <section class="py-16 md:py-24 bg-white border-b border-border-light">
      <div class="container-narrow">
        <h2 class="text-4xl md:text-5xl font-serif font-bold text-text-dark text-center mb-4">
          Lessons & Training
        </h2>
        <p class="text-center text-light-brown text-lg mb-16">
          Choose your lesson type and start your journey
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Private Riding Lessons -->
          <div class="card overflow-hidden hover:shadow-xl transition-shadow">
            <div class="h-48 bg-gradient-to-br from-amber-600 to-amber-800 flex items-center justify-center">
              <div class="text-6xl">🏇</div>
            </div>
            <div class="card-padding">
              <h3 class="font-serif text-2xl font-bold text-text-dark mb-3">
                Private Riding Lessons
              </h3>
              <p class="text-light-brown mb-6 leading-relaxed">
                One-on-one instruction tailored to your goals and experience level.
              </p>
              <router-link to="/lessons" class="inline-flex items-center text-accent-gold font-bold hover:text-warm-gold transition-colors">
                View Options
                <span class="ml-2">→</span>
              </router-link>
            </div>
          </div>

          <!-- Joined/Sibling Lessons -->
          <div class="card overflow-hidden hover:shadow-xl transition-shadow">
            <div class="h-48 bg-gradient-to-br from-green-600 to-green-800 flex items-center justify-center">
              <div class="text-6xl">👥</div>
            </div>
            <div class="card-padding">
              <h3 class="font-serif text-2xl font-bold text-text-dark mb-3">
                Joined / Sibling Lessons
              </h3>
              <p class="text-light-brown mb-6 leading-relaxed">
                Perfect for siblings or friends looking to ride together.
              </p>
              <router-link to="/lessons" class="inline-flex items-center text-accent-gold font-bold hover:text-warm-gold transition-colors">
                View Options
                <span class="ml-2">→</span>
              </router-link>
            </div>
          </div>

          <!-- Barrel & Pole -->
          <div class="card overflow-hidden hover:shadow-xl transition-shadow">
            <div class="h-48 bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
              <div class="text-6xl">🔥</div>
            </div>
            <div class="card-padding">
              <h3 class="font-serif text-2xl font-bold text-text-dark mb-3">
                Barrel & Pole Lessons
              </h3>
              <p class="text-light-brown mb-6 leading-relaxed">
                For more experienced riders looking to improve their skills.
              </p>
              <router-link to="/lessons" class="inline-flex items-center text-accent-gold font-bold hover:text-warm-gold transition-colors">
                View Options
                <span class="ml-2">→</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-16 md:py-24 bg-gradient-to-r from-dark-brown to-text-dark text-white overflow-hidden">
      <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text x=%2250%22 y=%2250%22 font-size=%2280%22>🐴</text></svg>'); background-size: 200px;"></div>
      <div class="relative z-10 container-narrow text-center">
        <h2 class="text-4xl md:text-5xl font-serif font-bold mb-4">
          Ready to Ride?
        </h2>
        <p class="text-xl mb-12 text-gray-100 max-w-2xl mx-auto">
          Check out my current availability and book your lesson online.
        </p>
        <router-link to="/book" class="btn-primary inline-block btn-large bg-accent-gold hover:bg-warm-gold text-text-dark">
          Book a Lesson
        </router-link>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
</script>
