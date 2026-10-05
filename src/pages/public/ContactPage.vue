<template>
  <PublicLayout>
    <section class="py-16 md:py-24">
      <div class="container-narrow max-w-2xl">
        <h1 class="text-4xl font-serif font-bold text-text-dark text-center mb-4">
          Get in Touch
        </h1>
        <p class="text-center text-light-brown text-lg mb-12">
          Have questions? We'd love to hear from you.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
          <div class="text-center">
            <div class="text-3xl mb-3">📧</div>
            <h3 class="font-bold mb-2">Email</h3>
            <a href="mailto:info@rjperformancehorses.com" class="text-accent-gold hover:text-warm-gold">
              info@rjperformancehorses.com
            </a>
          </div>
          <div class="text-center">
            <div class="text-3xl mb-3">📞</div>
            <h3 class="font-bold mb-2">Phone</h3>
            <a href="tel:+15551234567" class="text-accent-gold hover:text-warm-gold">
              (555) 123-4567
            </a>
          </div>
          <div class="text-center">
            <div class="text-3xl mb-3">📍</div>
            <h3 class="font-bold mb-2">Visit</h3>
            <p class="text-light-brown">
              Horse Ranch<br />
              Town, State
            </p>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="card card-padding">
          <h2 class="text-2xl font-serif font-bold text-text-dark mb-6">Send us a Message</h2>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="form-label">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="form-input"
                required
              />
            </div>
            <div>
              <label class="form-label">Email</label>
              <input
                v-model="form.email"
                type="email"
                class="form-input"
                required
              />
            </div>
            <div>
              <label class="form-label">Subject</label>
              <input
                v-model="form.subject"
                type="text"
                class="form-input"
                required
              />
            </div>
            <div>
              <label class="form-label">Message</label>
              <textarea
                v-model="form.message"
                class="form-textarea"
                rows="5"
                required
              />
            </div>
            <button type="submit" class="btn-primary w-full">
              Send Message
            </button>
          </form>
          <div v-if="submitted" class="mt-4 p-4 bg-green-100 text-green-700 rounded-md">
            Thank you! We'll get back to you soon.
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PublicLayout from '@/components/layouts/PublicLayout.vue'

const form = ref({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const submitted = ref(false)

const submitForm = () => {
  console.log('Form submitted:', form.value)
  submitted.value = true
  form.value = { name: '', email: '', subject: '', message: '' }
  setTimeout(() => {
    submitted.value = false
  }, 3000)
}
</script>
