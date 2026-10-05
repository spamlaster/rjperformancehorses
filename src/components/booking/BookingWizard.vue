<template>
  <div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Progress Bar -->
    <div class="h-2 bg-gray-200">
      <div
        class="h-full bg-accent-gold transition-all duration-300"
        :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
      />
    </div>

    <!-- Steps -->
    <div class="p-6 md:p-8 min-h-96">
      <!-- Step 0: Lesson Type -->
      <div v-show="currentStep === 0">
        <h2 class="text-2xl font-serif font-bold mb-6">Select a Lesson</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <button
            v-for="lesson in lessonTypes"
            :key="lesson.id"
            @click="selectLesson(lesson)"
            class="p-4 border-2 rounded-lg text-left transition-all"
            :class="
              selectedLessonType?.id === lesson.id
                ? 'border-accent-gold bg-yellow-50'
                : 'border-border-light hover:border-accent-gold'
            "
          >
            <h3 class="font-bold text-text-dark">{{ lesson.name }}</h3>
            <p class="text-sm text-light-brown mb-3">{{ lesson.description }}</p>
            <div class="flex justify-between items-end">
              <div>
                <span class="text-accent-gold font-bold">${{ lesson.price }}</span>
                <span class="text-sm text-light-brown ml-2">{{ lesson.durationMinutes }} min</span>
              </div>
              <span v-if="selectedLessonType?.id === lesson.id" class="text-accent-gold text-xl">✓</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Step 1: Date & Time -->
      <div v-show="currentStep === 1">
        <h2 class="text-2xl font-serif font-bold mb-6">Select Date & Time</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="form-label">Date</label>
            <input
              v-model="selectedDate"
              type="date"
              class="form-input"
              @change="updateAvailableTimes"
            />
          </div>
          <div>
            <label class="form-label">Time</label>
            <select
              v-model="selectedTime"
              class="form-select"
              :disabled="!selectedDate"
            >
              <option value="">Select a time...</option>
              <option
                v-for="time in availableTimes"
                :key="time"
                :value="time"
              >
                {{ formatTime(time) }}
              </option>
            </select>
          </div>
        </div>
        <div v-if="selectedDate && availableTimes.length === 0" class="mt-4 p-4 bg-yellow-100 text-yellow-800 rounded">
          No available times for this date. Please select another date.
        </div>
      </div>

      <!-- Step 2: Rider Information -->
      <div v-show="currentStep === 2">
        <h2 class="text-2xl font-serif font-bold mb-6">Rider Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">First Name *</label>
            <input
              v-model="riderInfo.firstName"
              type="text"
              class="form-input"
              required
            />
          </div>
          <div>
            <label class="form-label">Last Name *</label>
            <input
              v-model="riderInfo.lastName"
              type="text"
              class="form-input"
              required
            />
          </div>
          <div>
            <label class="form-label">Email *</label>
            <input
              v-model="riderInfo.email"
              type="email"
              class="form-input"
              required
            />
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input
              v-model="riderInfo.phone"
              type="tel"
              class="form-input"
            />
          </div>
          <div>
            <label class="form-label">Date of Birth</label>
            <input
              v-model="riderInfo.dateOfBirth"
              type="date"
              class="form-input"
            />
          </div>
          <div>
            <label class="form-label">Experience Level</label>
            <select
              v-model="riderInfo.experienceLevel"
              class="form-select"
            >
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Step 3: Waiver -->
      <div v-show="currentStep === 3">
        <h2 class="text-2xl font-serif font-bold mb-6">Waiver & Safety</h2>
        <div class="bg-gray-50 p-6 rounded-lg border border-border-light mb-6 max-h-64 overflow-y-auto">
          <h3 class="font-bold mb-4">RELEASE OF LIABILITY</h3>
          <div class="text-sm text-gray-700 space-y-3">
            <p>
              I acknowledge that horseback riding involves inherent risks including but not limited to falls, injuries, and property damage. I accept full responsibility for these risks.
            </p>
            <p>
              I confirm that I am in good physical health and able to participate in this activity. I have disclosed any medical conditions that may affect my ability to ride.
            </p>
            <p>
              I release and hold harmless RJ Performance Horses, its owners, instructors, employees, and agents from any liability for injuries or damages sustained during lessons or activities.
            </p>
            <p>
              I understand that helmets are required and agree to follow all safety instructions.
            </p>
            <p>
              <strong>This is a placeholder waiver for Phase 1. Production waivers should be reviewed by legal counsel.</strong>
            </p>
          </div>
        </div>
        <label class="flex items-start gap-3 mb-4">
          <input
            v-model="waiverSigned"
            type="checkbox"
            class="mt-1"
          />
          <span class="text-text-dark">
            I have read and agree to the waiver. I understand the risks involved and accept full responsibility.
          </span>
        </label>
      </div>

      <!-- Step 4: Confirmation -->
      <div v-show="currentStep === 4">
        <h2 class="text-2xl font-serif font-bold mb-6">Confirm Your Booking</h2>
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-bold text-text-dark mb-3">Lesson Details</h3>
            <div class="space-y-2 text-sm">
              <p><span class="text-light-brown">Lesson:</span> <span class="font-medium">{{ selectedLessonType?.name }}</span></p>
              <p><span class="text-light-brown">Date:</span> <span class="font-medium">{{ formatDate(selectedDate) }}</span></p>
              <p><span class="text-light-brown">Time:</span> <span class="font-medium">{{ formatTime(selectedTime) }}</span></p>
              <p><span class="text-light-brown">Price:</span> <span class="font-medium text-accent-gold">${{ selectedLessonType?.price }}</span></p>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-bold text-text-dark mb-3">Rider Information</h3>
            <div class="space-y-2 text-sm">
              <p><span class="text-light-brown">Name:</span> <span class="font-medium">{{ riderInfo.firstName }} {{ riderInfo.lastName }}</span></p>
              <p><span class="text-light-brown">Email:</span> <span class="font-medium">{{ riderInfo.email }}</span></p>
              <p><span class="text-light-brown">Phone:</span> <span class="font-medium">{{ riderInfo.phone || '—' }}</span></p>
              <p><span class="text-light-brown">Experience:</span> <span class="font-medium capitalize">{{ riderInfo.experienceLevel }}</span></p>
            </div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg border border-green-200">
            <p class="text-sm text-green-800">
              ✓ Waiver signed and accepted
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <div class="px-6 md:px-8 py-6 bg-gray-50 border-t border-border-light flex justify-between items-center gap-4">
      <div class="text-sm text-light-brown">
        Step {{ currentStep + 1 }} of {{ totalSteps }}
      </div>
      <div class="flex gap-3">
        <button
          v-if="currentStep > 0"
          @click="prevStep"
          class="btn-secondary"
        >
          Back
        </button>
        <button
          v-if="!isLastStep"
          @click="nextStep"
          :disabled="!canGoNext"
          :class="!canGoNext && 'opacity-50 cursor-not-allowed'"
          class="btn-primary"
        >
          Next
        </button>
        <button
          v-else
          @click="confirmBooking"
          class="btn-primary bg-green-600 hover:bg-green-700"
        >
          Confirm Booking
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useBookingStore } from '@/stores/bookingStore'
import { useAdminStore } from '@/stores/adminStore'
import { format, parseISO } from 'date-fns'
import type { LessonType } from '@/types/models'

const bookingStore = useBookingStore()
const adminStore = useAdminStore()

const currentStep = computed(() => bookingStore.currentStep)
const totalSteps = computed(() => bookingStore.totalSteps)
const canGoNext = computed(() => bookingStore.canGoNext)
const isLastStep = computed(() => bookingStore.isLastStep)

const lessonTypes = computed(() => adminStore.lessonTypes)
const selectedLessonType = computed(() => bookingStore.selectedLessonType)
const selectedDate = computed({
  get: () => bookingStore.selectedDate,
  set: (val) => bookingStore.setDateAndTime(val, bookingStore.selectedTime),
})
const selectedTime = computed({
  get: () => bookingStore.selectedTime,
  set: (val) => bookingStore.setDateAndTime(bookingStore.selectedDate, val),
})
const riderInfo = computed(() => bookingStore.riderInfo)
const waiverSigned = computed({
  get: () => bookingStore.waiverSigned,
  set: (val) => bookingStore.setWaiverSigned(val),
})

const availableTimes = computed(() => {
  if (!selectedDate.value) return []
  const slots = adminStore.getAvailableSlots(selectedDate.value)
  return [...new Set(slots.map(s => s.startTime))].sort()
})

const selectLesson = (lesson: LessonType) => {
  bookingStore.setLessonType(lesson)
}

const nextStep = () => {
  bookingStore.nextStep()
}

const prevStep = () => {
  bookingStore.prevStep()
}

const updateAvailableTimes = () => {
  bookingStore.setDateAndTime(selectedDate.value, '')
}

const confirmBooking = () => {
  const result = bookingStore.submitBooking()
  alert('Booking confirmed! This is a Phase 1 demo. Data logged to console.')
  console.log('Booking data:', result)
  bookingStore.resetWizard()
}

const formatTime = (time: string) => {
  if (!time) return ''
  return format(parseISO(`2024-01-01T${time}`), 'h:mm a')
}

const formatDate = (date: string) => {
  if (!date) return ''
  return format(parseISO(date), 'MMMM dd, yyyy')
}
</script>
