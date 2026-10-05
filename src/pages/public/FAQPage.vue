<template>
  <PublicLayout>
    <section class="py-16 md:py-24">
      <div class="container-narrow max-w-2xl">
        <h1 class="text-4xl font-serif font-bold text-text-dark text-center mb-4">
          Frequently Asked Questions
        </h1>
        <p class="text-center text-light-brown text-lg mb-12">
          Find answers to common questions about lessons and our programs.
        </p>

        <div class="space-y-4">
          <div class="card card-padding">
            <button
              @click="toggleFaq(0)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">What experience level do I need?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(0) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(0)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                We offer lessons for all experience levels, from complete beginners to advanced riders. Rylie will assess your skill level and create a customized lesson plan.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(1)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">What age can children start?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(1) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(1)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                Children can typically start as young as 4-5 years old, depending on their maturity and comfort with horses. Contact us to discuss your child's readiness.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(2)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">How do I book a lesson?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(2) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(2)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                Click the "Book a Lesson" button on our website to view available times and complete your booking. You'll need to provide rider information and sign our waiver.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(3)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">What if I need to cancel?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(3) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(3)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                We require 24-hour notice for cancellations to receive a refund or reschedule. No-shows are forfeited.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(4)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">What should I wear?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(4) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(4)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                Wear long pants, closed-toe shoes with a heel (boots are ideal), and a helmet. A t-shirt or long-sleeve shirt works well. Helmets are provided if needed.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(5)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">Are waivers required?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(5) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(5)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                Yes, a waiver must be signed by the rider (or parent/guardian for minors) before the first lesson. This protects both you and us.
              </p>
            </div>
          </div>

          <div class="card card-padding">
            <button
              @click="toggleFaq(6)"
              class="w-full flex justify-between items-start cursor-pointer"
            >
              <h3 class="font-bold text-text-dark text-left">Do you offer group discounts?</h3>
              <span class="text-accent-gold text-xl ml-4">{{ openFaq.has(6) ? '−' : '+' }}</span>
            </button>
            <div v-if="openFaq.has(6)" class="mt-4 text-light-brown border-t border-border-light pt-4">
              <p>
                Yes! Multi-lesson packages and group bookings receive discounts. Contact us for custom pricing.
              </p>
            </div>
          </div>
        </div>

        <!-- Contact CTA -->
        <div class="text-center mt-12">
          <p class="text-light-brown mb-4">Have a question we didn't answer?</p>
          <router-link to="/contact" class="btn-primary">
            Get in Touch
          </router-link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PublicLayout from '@/components/layouts/PublicLayout.vue'

const openFaq = ref<Set<number>>(new Set())

const toggleFaq = (index: number) => {
  if (openFaq.value.has(index)) {
    openFaq.value.delete(index)
  } else {
    openFaq.value.add(index)
  }
}
</script>
