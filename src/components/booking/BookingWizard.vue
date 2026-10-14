<template>
  <div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Step Indicators -->
    <div class="bg-gradient-to-r from-dark-brown to-text-dark text-white p-4 md:p-5">
      <div class="flex items-center justify-between max-w-4xl mx-auto mb-3">
        <div
          v-for="(_, index) in totalSteps"
          :key="index"
          class="flex flex-col items-center flex-1"
        >
          <div
            :class="[
            'w-8 h-8 md:w-9 md:h-9 rounded-full flex items-center justify-center font-bold mb-1 transition-all',
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
    <div class="p-4 md:p-6">
      <!-- Step 0: Lesson Type -->
      <div v-show="currentStep === 0">
        <h2 class="text-2xl font-serif font-bold mb-4">Select a Lesson</h2>
        <p class="text-light-brown mb-4">Reserve a weekly lesson. Your day and time stay yours until you cancel.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <button
            v-for="lesson in lessonTypes"
            :key="lesson.id"
            @click="selectLesson(lesson)"
            class="p-3 border-2 rounded-lg text-left transition-all"
            :class="
              selectedLessonType?.id === lesson.id
                ? 'border-accent-gold bg-yellow-50'
                : 'border-border-light hover:border-accent-gold'
            "
          >
            <h3 class="font-bold text-text-dark">{{ lesson.name }}</h3>
            <p class="text-sm text-light-brown mb-2 line-clamp-2">{{ lesson.description }}</p>
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
        <div class="flex items-center justify-between gap-3 mb-4">
          <h2 class="text-2xl font-serif font-bold">Select Date & Time</h2>
          <span class="hidden sm:inline text-xs text-light-brown">Mountain Time</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] gap-4">
          <div>
            <label class="form-label">First session date</label>
            <input
              v-model="selectedDate"
              type="date"
              class="form-input"
            />
          </div>
          <div aria-live="polite">
            <label class="form-label mb-2">Available times</label>
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
        <p v-if="selectedTime" class="mt-3 text-sm text-light-brown">Selected time: {{ formatTime(selectedTime) }}</p>
      </div>

      <!-- Step 2: Contact & Rider Information -->
      <div v-show="currentStep === 2">
        <h2 class="text-2xl font-serif font-bold mb-6">Contact & Rider Information</h2>
        <p v-if="!savedAccount" class="mb-4 text-light-brown"><router-link to="/rider/sign-in?returnTo=/book" class="underline font-semibold">Sign in or create a rider account</router-link> to reuse your saved riders.</p>

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
            <h4 class="font-bold text-text-dark mb-3">Rider {{ index + 1 }}</h4>
            <div v-if="savedAccount?.riders.length" class="mb-3">
              <label :for="`saved-rider-${index}`" class="form-label">Choose a saved rider</label>
              <select :id="`saved-rider-${index}`" :value="rider.id || ''" class="form-select" @change="selectSavedRider(index, ($event.target as HTMLSelectElement).value)">
                <option value="">Enter a new rider</option>
                <option v-for="saved in savedAccount.riders" :key="saved.id" :value="saved.id">{{ saved.first_name }} {{ saved.last_name }}</option>
              </select>
            </div>
            <div v-if="index === 0 && !rider.id" class="mb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  :checked="rider.sameAsCustomer"
                  @change="toggleSameAsCustomer(index)"
                  type="checkbox"
                  class="rounded"
                />
                <span class="text-text-dark">I am the rider</span>
              </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="!usesContactName(index) && !rider.id">
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
              <div v-if="!usesContactName(index) && !rider.id">
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
              <div v-if="!rider.id" class="md:col-span-2">
                <label class="form-label">Date of Birth *</label>
                <input
                  :value="rider.dateOfBirth"
                  @input="updateRider(index, { dateOfBirth: ($event.target as HTMLInputElement).value })"
                  type="date"
                  class="form-input"
                  required
                />
              </div>
            </div>

            <div v-if="usesContactName(index)" class="text-sm text-light-brown mt-3">
              Using your contact name above.
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Review -->
      <div v-show="currentStep === 3">
        <h2 class="text-2xl font-serif font-bold mb-6">Review Your Booking</h2>

        <p class="mb-4 text-light-brown">This reserves the same day and time every week, starting on the date below, until you cancel. You can skip individual weeks without giving up your standing time.</p>
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
                <span class="text-light-brown">Price per session:</span>
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
                <p class="font-semibold">Rider {{ index + 1 }}{{ usesContactName(index) ? ' (you)' : '' }}</p>
                <p class="text-light-brown">
                  {{ riderName(index) }}
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
            Your Weekly Lesson Is Reserved!
          </h2>
          <p class="text-light-brown mb-6">
            Your day and time are reserved every week until you cancel.
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
                <span class="text-light-brown">Rider{{ riders.length > 1 ? 's' : '' }}:</span> {{ riders.map((_, i) => riderName(i)).join(', ') }}
              </div>
              <div v-if="createdBookingId" class="pt-3 border-t border-border-light">
                <span class="text-light-brown">Booking ID:</span> {{ createdBookingId }}
              </div>
            </div>
          </div>

          <div v-if="managementLink" class="max-w-md mx-auto text-left mb-6">
            <h3 class="font-bold mb-2">Manage your weekly reservation</h3>
            <p class="text-sm text-light-brown mb-3">Save this private link to skip a week or cancel all future sessions. Anyone with this link can manage your reservation.</p>
            <a :href="managementLink" class="btn-primary inline-block mb-3">Manage Weekly Reservation</a>
            <label for="management-link" class="form-label">Your private management link</label>
            <input id="management-link" :value="managementLink" readonly class="form-input text-sm" @focus="($event.target as HTMLInputElement).select()" />
            <button class="btn-secondary mt-3 text-sm" @click="copyManagementLink">Copy Link</button>
            <p v-if="copyNotice" role="status" class="mt-2 text-sm">{{ copyNotice }}</p>
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
          v-if="currentStep === 3"
          @click="submitBooking"
          :disabled="submittingBooking"
          class="btn-primary"
        >
          {{ submittingBooking ? 'Booking...' : 'Reserve Weekly Lesson' }}
        </button>
        <button
          v-else-if="!isLastStep"
          @click="nextStep"
          :disabled="!canGoNext || submittingBooking"
          :class="(!canGoNext || submittingBooking) && 'opacity-50 cursor-not-allowed'"
          class="btn-primary"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { loadRiderAccount, type RiderAccount } from '@/services/riderAccountService'
import { useBookingStore } from '@/stores/bookingStore'
import { fetchLessonTypes, fetchBusinessTimezone } from '@/services/apiService'
import { getBookableSlots, formatTimeForDisplay, type BookableSlot } from '@/services/availabilityService'
import { createGuestBooking } from '@/services/guestBookingService'
import type { LessonType } from '@/types/models'
import { format, parseISO } from 'date-fns'

const bookingStore = useBookingStore()
const savedAccount = ref<RiderAccount | null>(null)
const lessonTypes = ref<LessonType[]>([])
const businessTimezone = ref('')
const slots = ref<BookableSlot[]>([])
const loadingSlots = ref(false)
const availabilityError = ref('')
const submittingBooking = ref(false)
const bookingSuccess = ref(false)
const bookingError = ref('')
const managementLink = ref('')
const copyNotice = ref('')
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
    // Build start/end times as ISO strings representing business-local wall times
    // selectedDate is YYYY-MM-DD and selectedTime is HH:MM (both business-local)
    // Backend expects TIMESTAMP (wall-clock time), not TIMESTAMPTZ (absolute moments)
    // We construct ISO strings directly without using new Date() which uses browser timezone
    const startTime = `${selectedDate.value}T${selectedTime.value}:00`

    // Calculate end time by adding lesson duration to start time
    const [startHours, startMinutes] = selectedTime.value.split(':').map(Number)
    const durationMinutes = selectedLessonType.value?.durationMinutes || 60
    const totalMinutes = startHours * 60 + startMinutes + durationMinutes
    const endHours = Math.floor(totalMinutes / 60) % 24
    const endMinutes = totalMinutes % 60

    // Check if end time crosses into next day
    const dayOffset = Math.floor((startHours * 60 + startMinutes + durationMinutes) / (24 * 60))
    const [year, month, day] = selectedDate.value.split('-').map(Number)
    const endDate = new Date(year, month - 1, day + dayOffset)
    const endDateStr = `${endDate.getFullYear()}-${String(endDate.getMonth() + 1).padStart(2, '0')}-${String(endDate.getDate()).padStart(2, '0')}`
    const endTime = `${endDateStr}T${String(endHours).padStart(2, '0')}:${String(endMinutes).padStart(2, '0')}:00`

    // Prepare riders array
    const ridersData = riders.value.map((rider, index) => {
      const usesContact = usesContactName(index)
      return {
        id: rider.id,
        firstName: (usesContact ? customerInfo.value.firstName : rider.firstName).trim(),
        lastName: (usesContact ? customerInfo.value.lastName : rider.lastName).trim(),
        dateOfBirth: rider.dateOfBirth || undefined,
      }
    })

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
      managementLink.value = result.managementToken ? `${window.location.origin}/reservation#${result.managementToken}` : ''
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

async function copyManagementLink() {
  try {
    await navigator.clipboard.writeText(managementLink.value)
    copyNotice.value = 'Link copied. Save it somewhere you can find later.'
  } catch {
    copyNotice.value = 'Select and copy your private link from the field above.'
  }
}

function resetAndStartOver() {
  bookingStore.resetWizard()
  bookingSuccess.value = false
  managementLink.value = ''
  copyNotice.value = ''
  bookingError.value = ''
}

function updateRider(index: number, info: any) {
  bookingStore.updateRider(index, info)
}

function selectSavedRider(index: number, id: string) {
  const saved = savedAccount.value?.riders.find(r => r.id === id)
  bookingStore.updateRider(index, {
    id: saved?.id, firstName: saved?.first_name || '', lastName: saved?.last_name || '',
    dateOfBirth: saved?.date_of_birth?.slice(0, 10) || '', sameAsCustomer: false,
  })
}

function toggleSameAsCustomer(index: number) {
  const newValue = !riders.value[index].sameAsCustomer
  if (newValue) {
    // Auto-populate rider info from customer when checking "I am the rider"
    bookingStore.updateRider(index, {
      sameAsCustomer: true,
      firstName: customerInfo.value.firstName,
      lastName: customerInfo.value.lastName,
    })
  } else {
    // Clear auto-populated fields when unchecking
    bookingStore.updateRider(index, {
      sameAsCustomer: false,
      firstName: '',
      lastName: '',
    })
  }
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

// Only Rider 1 can be the person booking; every other rider always has their own name.
function usesContactName(index: number): boolean {
  return index === 0 && !!riders.value[0]?.sameAsCustomer
}

function riderName(index: number): string {
  const rider = riders.value[index]
  return usesContactName(index)
    ? `${customerInfo.value.firstName} ${customerInfo.value.lastName}`
    : `${rider.firstName} ${rider.lastName}`
}

watch([() => selectedLessonType.value?.id, selectedDate], loadSlots, { immediate: true, flush: 'sync' })

onMounted(async () => {
  bookingStore.setStep(0)
  try {
    savedAccount.value = await loadRiderAccount()
    if (savedAccount.value) {
      const customer = savedAccount.value.customer
      bookingStore.updateCustomerInfo({ firstName: customer.first_name, lastName: customer.last_name, email: customer.email, phone: customer.phone || '' })
    }
  } catch {
    // Guest booking remains available if account loading fails.
  }
  const availableLessons = await fetchLessonTypes(true)
  // Keep the existing lesson ID for availability and booking requests.
  const combinedLesson = availableLessons.find(lesson => lesson.name === 'Barrel Racing & Pole Bending')
    || availableLessons.find(lesson => lesson.name === 'Barrel Racing')
    || availableLessons.find(lesson => lesson.name === 'Pole Bending')
  lessonTypes.value = availableLessons
    .filter(lesson => !['Weekly Barrel Training', 'Extended Group Lesson'].includes(lesson.name))
    .filter(lesson => !['Barrel Racing', 'Pole Bending', 'Barrel Racing & Pole Bending'].includes(lesson.name) || lesson === combinedLesson)
    .map(lesson => lesson === combinedLesson ? {
      ...lesson,
      name: 'Barrel Racing & Pole Bending',
      description: 'Specialized training in barrel racing, pole bending patterns, and speed control.',
    } : lesson)
  businessTimezone.value = (await fetchBusinessTimezone()) || 'business local time'
})

const selectLesson = (lesson: LessonType) => bookingStore.setLessonType(lesson)
const nextStep = () => bookingStore.nextStep()
const prevStep = () => bookingStore.prevStep()
const getStepLabel = (index: number) => ['Lesson Type', 'Date & Time', 'Contact Info', 'Review', 'Confirmation'][index] || ''
</script>
