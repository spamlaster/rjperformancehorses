<template>
  <div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Step Indicators -->
    <div class="bg-gradient-to-r from-dark-brown to-text-dark text-white p-6">
      <div class="flex items-center justify-between max-w-4xl mx-auto mb-4">
        <div
          v-for="(_, index) in totalSteps"
          :key="index"
          class="flex flex-col items-center flex-1"
        >
          <div
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center font-bold mb-2 transition-all',
              index < currentStep
                ? 'bg-green-500'
                : index === currentStep
                ? 'bg-accent-gold text-text-dark'
                : 'bg-gray-500'
            ]"
          >
            {{ index < currentStep ? '✓' : index + 1 }}
          </div>
          <span class="text-xs font-medium text-center leading-tight">
            {{ getStepLabel(index) }}
          </span>
        </div>
      </div>
      <!-- Progress Bar -->
      <div class="h-1 bg-gray-700 rounded-full overflow-hidden">
        <div
          class="h-full bg-accent-gold transition-all duration-300"
          :style="{ width: `${((currentStep + 1) / totalSteps) * 100}%` }"
        />
      </div>
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
            />
          </div>
          <div aria-live="polite">
            <label class="form-label">Available times</label>
            <p v-if="loadingSlots" role="status">Loading available times…</p>
            <div v-else-if="availabilityError" role="alert">
              <p>{{ availabilityError }}</p>
              <button class="btn-secondary mt-2" @click="loadSlots">Try again</button>
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <button v-for="slot in slots" :key="slot.startTime"
                :aria-pressed="selectedTime === slot.startTime"
                :class="selectedTime === slot.startTime ? 'btn-primary' : 'btn-secondary'"
                @click="selectedTime = slot.startTime">{{ formatTime(slot.startTime) }}</button>
            </div>
          </div>
        </div>
        <p v-if="selectedDate && !loadingSlots && !availabilityError && !slots.length" class="mt-4" role="status">
          No available times for this date. Please select another date.
        </p>
        <p v-if="selectedTime" class="mt-4 text-sm text-light-brown">
          All times are shown in Mountain Time.
        </p>
      </div>

      <!-- Step 2: Contact & Rider Information -->
      <div v-show="currentStep === 2">
        <h2 class="text-2xl font-serif font-bold mb-6">Contact & Rider Information</h2>

        <!-- Customer Information Section -->
        <div class="mb-8">
          <h3 class="text-lg font-bold text-text-dark mb-4">Your Contact Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">First Name *</label>
              <input
                v-model="customerInfo.firstName"
                type="text"
                class="form-input"
                placeholder="John"
                required
              />
            </div>
            <div>
              <label class="form-label">Last Name *</label>
              <input
                v-model="customerInfo.lastName"
                type="text"
                class="form-input"
                placeholder="Doe"
                required
              />
            </div>
            <div>
              <label class="form-label">Email *</label>
              <input
                v-model="customerInfo.email"
                type="email"
                class="form-input"
                placeholder="john@example.com"
                required
              />
            </div>
            <div>
              <label class="form-label">Phone</label>
              <input
                v-model="customerInfo.phone"
                type="tel"
                class="form-input"
                placeholder="(555) 123-4567"
              />
            </div>
          </div>
        </div>

        <!-- Riders Information Section -->
        <div>
          <h3 class="text-lg font-bold text-text-dark mb-4">
            Rider{{ riderCount > 1 ? 's' : '' }} ({{ riderCount }} required)
          </h3>
          <div
            v-for="(rider, index) in riders"
            :key="index"
            class="mb-6 p-4 border-l-4 border-accent-gold bg-gray-50 rounded"
          >
            <div v-if="riderCount === 1" class="mb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  :checked="rider.sameAsCustomer"
                  @change="updateRider(index, { sameAsCustomer: !rider.sameAsCustomer })"
                  type="checkbox"
                  class="rounded"
                />
                <span class="text-text-dark">I am the rider</span>
              </label>
            </div>

            <div v-if="!rider.sameAsCustomer || riderCount > 1" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">First Name *</label>
                <input
                  :value="rider.firstName"
                  @input="updateRider(index, { firstName: ($event.target as HTMLInputElement).value })"
                  type="text"
                  class="form-input"
                  placeholder="Jane"
                  required
                />
              </div>
              <div>
                <label class="form-label">Last Name *</label>
                <input
                  :value="rider.lastName"
                  @input="updateRider(index, { lastName: ($event.target as HTMLInputElement).value })"
                  type="text"
                  class="form-input"
                  placeholder="Doe"
                  required
                />
              </div>
              <div class="md:col-span-2">
                <label class="form-label">Date of Birth</label>
                <input
                  :value="rider.dateOfBirth"
                  @input="updateRider(index, { dateOfBirth: ($event.target as HTMLInputElement).value })"
                  type="date"
                  class="form-input"
                />
              </div>
            </div>

            <div v-if="rider.sameAsCustomer && riderCount === 1" class="text-sm text-light-brown mt-3">
              Using your contact information above.
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Review -->
      <div v-show="currentStep === 3">
        <h2 class="text-2xl font-serif font-bold mb-6">Review Your Booking</h2>

        <div class="space-y-6">
          <!-- Lesson Details -->
          <div class="p-4 border border-border-light rounded-lg">
            <h3 class="font-bold text-text-dark mb-3">Lesson Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-light-brown">Lesson Type:</span>
                <p class="font-semibold">{{ selectedLessonType?.name }}</p>
              </div>
              <div>
                <span class="text-light-brown">Price:</span>
                <p class="font-semibold">${{ selectedLessonType?.price }}</p>
              </div>
              <div>
                <span class="text-light-brown">Duration:</span>
                <p class="font-semibold">{{ selectedLessonType?.durationMinutes }} minutes</p>
              </div>
              <div>
                <span class="text-light-brown">Date & Time:</span>
                <p class="font-semibold">{{ formatDate(selectedDate) }} at {{ formatTime(selectedTime) }}</p>
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="p-4 border border-border-light rounded-lg">
            <h3 class="font-bold text-text-dark mb-3">Contact Information</h3>
            <div class="text-sm">
              <p><span class="text-light-brown">Name:</span> {{ customerInfo.firstName }} {{ customerInfo.lastName }}</p>
              <p><span class="text-light-brown">Email:</span> {{ customerInfo.email }}</p>
              <p v-if="customerInfo.phone"><span class="text-light-brown">Phone:</span> {{ customerInfo.phone }}</p>
            </div>
          </div>

          <!-- Riders -->
          <div class="p-4 border border-border-light rounded-lg">
            <h3 class="font-bold text-text-dark mb-3">Rider{{ riderCount > 1 ? 's' : '' }}</h3>
            <div class="space-y-3 text-sm">
              <div v-for="(rider, index) in riders" :key="index" class="pb-3 last:pb-0 last:border-0 border-b border-border-light">
                <p class="font-semibold">
                  {{ rider.sameAsCustomer ? 'You (Rider ' : 'Rider ' }}{{ index + 1 }}{{ rider.sameAsCustomer ? ')' : '' }}
                </p>
                <p v-if="!rider.sameAsCustomer" class="text-light-brown">
                  {{ rider.firstName }} {{ rider.lastName }}
                  <span v-if="rider.dateOfBirth">(DOB: {{ rider.dateOfBirth }})</span>
                </p>
              </div>
            </div>
          </div>

          <button @click="bookingStore.setStep(2)" class="text-accent-gold hover:text-dark-brown font-semibold text-sm">
            ← Edit Information
          </button>
        </div>
      </div>

      <!-- Step 4: Confirmation -->
      <div v-show="currentStep === 4">
        <div v-if="bookingSuccess" class="text-center py-8">
          <div class="text-5xl mb-4">✓</div>
          <h2 class="text-3xl font-serif font-bold text-green-600 mb-4">
            Your Lesson Has Been Booked!
          </h2>
          <p class="text-light-brown mb-6">
            A confirmation will be sent to {{ customerInfo.email }}
          </p>

          <div class="bg-gray-50 p-6 rounded-lg mb-6 text-left max-w-md mx-auto">
            <h3 class="font-bold text-text-dark mb-4">Booking Summary</h3>
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-light-brown">Lesson:</span> {{ selectedLessonType?.name }}
              </div>
              <div>
                <span class="text-light-brown">Date & Time:</span> {{ formatDate(selectedDate) }} at {{ formatTime(selectedTime) }}
              </div>
              <div>
                <span class="text-light-brown">Rider:</span> {{ getDisplayRiderName() }}
              </div>
              <div v-if="createdBookingId" class="pt-3 border-t border-border-light">
                <span class="text-light-brown">Booking ID:</span> {{ createdBookingId }}
              </div>
            </div>
          </div>

          <button
            @click="resetAndStartOver"
            class="btn-primary"
          >
            Book Another Lesson
          </button>
        </div>

        <div v-else>
          <h2 class="text-2xl font-serif font-bold text-text-dark mb-6">Completing Your Booking...</h2>
          <p v-if="bookingError" class="text-red-600 mb-4">{{ bookingError }}</p>
          <p v-else class="text-light-brown">Please wait while we process your booking...</p>

          <button
            v-if="bookingError"
            @click="bookingStore.setStep(3)"
            class="btn-secondary mt-4"
          >
            Back to Review
          </button>
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
          :disabled="currentStep === 4 && submittingBooking"
          class="btn-secondary"
        >
          Back
        </button>
        <button
          v-if="!isLastStep"
          @click="nextStep"
          :disabled="!canGoNext || submittingBooking"
          :class="(!canGoNext || submittingBooking) && 'opacity-50 cursor-not-allowed'"
          class="btn-primary"
        >
          Next
        </button>
        <button
          v-else-if="currentStep === 3"
          @click="submitBooking"
          :disabled="submittingBooking"
          class="btn-primary"
        >
          {{ submittingBooking ? 'Booking...' : 'Book Lesson' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useBookingStore } from '@/stores/bookingStore'
import { fetchLessonTypes } from '@/services/supabaseService'
import { getBookableSlots, formatTimeForDisplay, type BookableSlot } from '@/services/availabilityService'
import { createGuestBooking } from '@/services/guestBookingService'
import { supabase } from '@/lib/supabase'
import type { LessonType } from '@/types/models'
import { format, parseISO } from 'date-fns'

const bookingStore = useBookingStore()
const lessonTypes = ref<LessonType[]>([])
const businessTimezone = ref('')
const slots = ref<BookableSlot[]>([])
const loadingSlots = ref(false)
const availabilityError = ref('')
const submittingBooking = ref(false)
const bookingSuccess = ref(false)
const bookingError = ref('')
let requestId = 0

const currentStep = computed(() => bookingStore.currentStep)
const totalSteps = 5
const isLastStep = computed(() => currentStep.value === totalSteps - 1)
const canGoNext = computed(() => bookingStore.canGoNext)
const selectedLessonType = computed(() => bookingStore.selectedLessonType)
const selectedDate = computed({
  get: () => bookingStore.selectedDate,
  set: (val: string) => bookingStore.setDateAndTime(val, ''),
})
const selectedTime = computed({
  get: () => bookingStore.selectedTime,
  set: (val: string) => bookingStore.setDateAndTime(bookingStore.selectedDate, val),
})
const customerInfo = computed(() => bookingStore.customerInfo)
const riders = computed(() => bookingStore.riders)
const riderCount = computed(() => bookingStore.getRiderCount())
const createdBookingId = computed(() => bookingStore.createdBookingId)

async function loadSlots() {
  const id = ++requestId
  selectedTime.value = ''
  slots.value = []
  availabilityError.value = ''
  loadingSlots.value = false
  if (!selectedDate.value || !selectedLessonType.value) return
  loadingSlots.value = true
  try {
    const result = await getBookableSlots(selectedLessonType.value.id, selectedDate.value)
    if (id === requestId) slots.value = result.filter(slot => slot.available)
  } catch {
    if (id === requestId) availabilityError.value = 'Unable to load availability. Please try again.'
  } finally {
    if (id === requestId) loadingSlots.value = false
  }
}

async function submitBooking() {
  submittingBooking.value = true
  bookingError.value = ''

  try {
    // Build start/end times as ISO strings
    const [year, month, day] = selectedDate.value.split('-')
    const [hours, minutes] = selectedTime.value.split(':')
    const startTime = new Date(
      parseInt(year),
      parseInt(month) - 1,
      parseInt(day),
      parseInt(hours),
      parseInt(minutes)
    ).toISOString()

    const endTime = new Date(
      new Date(startTime).getTime() + (selectedLessonType.value?.durationMinutes || 60) * 60000
    ).toISOString()

    // Prepare riders array
    const ridersData = riders.value.map((rider, index) => ({
      firstName: rider.sameAsCustomer && index === 0 ? customerInfo.value.firstName : rider.firstName,
      lastName: rider.sameAsCustomer && index === 0 ? customerInfo.value.lastName : rider.lastName,
      dateOfBirth: rider.dateOfBirth || undefined,
    }))

    const result = await createGuestBooking({
      lessonTypeId: selectedLessonType.value?.id || '',
      startTime,
      endTime,
      customer: {
        firstName: customerInfo.value.firstName,
        lastName: customerInfo.value.lastName,
        email: customerInfo.value.email,
        phone: customerInfo.value.phone,
      },
      riders: ridersData,
    })

    if (result.success && result.bookingId) {
      bookingStore.setBookingConfirmed(result.bookingId)
      bookingSuccess.value = true
      bookingStore.setStep(4)
    } else {
      bookingError.value = result.error || 'Failed to create booking'
    }
  } catch (err) {
    bookingError.value = 'An unexpected error occurred. Please try again.'
    console.error(err)
  } finally {
    submittingBooking.value = false
  }
}

function resetAndStartOver() {
  bookingStore.resetWizard()
  bookingSuccess.value = false
  bookingError.value = ''
}

function updateRider(index: number, info: any) {
  bookingStore.updateRider(index, info)
}

function formatTime(timeStr: string): string {
  return formatTimeForDisplay(timeStr)
}

function formatDate(dateStr: string): string {
  try {
    return format(parseISO(dateStr), 'EEEE, MMMM d, yyyy')
  } catch {
    return dateStr
  }
}

function getDisplayRiderName(): string {
  if (riders.value.length === 1) {
    if (riders.value[0].sameAsCustomer) {
      return `${customerInfo.value.firstName} ${customerInfo.value.lastName}`
    }
    return `${riders.value[0].firstName} ${riders.value[0].lastName}`
  }
  return riders.value.map((r, i) => {
    if (i === 0 && r.sameAsCustomer) {
      return `${customerInfo.value.firstName} ${customerInfo.value.lastName}`
    }
    return `${r.firstName} ${r.lastName}`
  }).join(', ')
}

watch([() => selectedLessonType.value?.id, selectedDate], loadSlots, { immediate: true, flush: 'sync' })

onMounted(async () => {
  bookingStore.setStep(0)
  lessonTypes.value = await fetchLessonTypes(true)
  const { data } = await supabase.from('business_config').select('config_value').eq('config_key', 'business_timezone').single()
  businessTimezone.value = data?.config_value || 'business local time'
})

const selectLesson = (lesson: LessonType) => bookingStore.setLessonType(lesson)
const nextStep = () => {
  if (currentStep.value === 2) {
    // Auto-populate first rider if "I am the rider" is checked
    if (riders.value[0].sameAsCustomer && riders.value.length === 1) {
      riders.value[0].firstName = customerInfo.value.firstName
      riders.value[0].lastName = customerInfo.value.lastName
    }
  }
  bookingStore.nextStep()
}
const prevStep = () => bookingStore.prevStep()
const getStepLabel = (index: number) => ['Lesson Type', 'Date & Time', 'Contact Info', 'Review', 'Confirmation'][index] || ''
</script>
