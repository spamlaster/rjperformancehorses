<template>
  <footer class="bg-dark-brown text-cream border-t border-warm-gold">
    <div class="container-narrow py-12 md:py-16">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <!-- Brand -->
        <div>
          <h3 class="font-serif text-lg font-bold mb-4 text-accent-gold">RJ Performance Horses</h3>
          <p class="text-sm text-gray-300">
            Professional riding lessons and training in the heart of horse country.
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="font-bold mb-4 text-accent-gold">Quick Links</h4>
          <ul class="space-y-2 text-sm">
            <li><router-link to="/lessons" class="hover:text-accent-gold">Lessons</router-link></li>
            <li><router-link to="/pricing" class="hover:text-accent-gold">Pricing</router-link></li>
            <li><router-link to="/about" class="hover:text-accent-gold">About Rylie</router-link></li>
            <li><router-link to="/faq" class="hover:text-accent-gold">FAQ</router-link></li>
          </ul>
        </div>

        <!-- Contact -->
        <div>
          <h4 class="font-bold mb-4 text-accent-gold">Contact</h4>
          <p class="text-sm mb-2">
            <a href="mailto:info@rjperformancehorses.com" class="hover:text-accent-gold">
              info@rjperformancehorses.com
            </a>
          </p>
          <p class="text-sm mb-2">
            <a href="tel:+1234567890" class="hover:text-accent-gold">
              (123) 456-7890
            </a>
          </p>
        </div>

        <!-- Booking -->
        <div>
          <h4 class="font-bold mb-4 text-accent-gold">Get Started</h4>
          <p class="text-sm mb-4">Ready to book your first lesson?</p>
          <router-link to="/book" class="btn-primary inline-block text-sm">
            Book Now
          </router-link>
        </div>
      </div>

      <!-- Bottom -->
      <div class="border-t border-warm-gold pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-400">
        <p>&copy; 2024 RJ Performance Horses. All rights reserved.</p>
        <div class="flex gap-6 mt-4 md:mt-0">
          <a href="#" class="hover:text-accent-gold">Privacy Policy</a>
          <a href="#" class="hover:text-accent-gold">Terms of Service</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
// No component logic needed
</script>
