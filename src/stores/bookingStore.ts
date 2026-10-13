import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { LessonType } from '@/types/models'

export interface GuestCustomer {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
}

export interface RiderInfo {
  firstName: string;
  lastName: string;
  dateOfBirth?: string;
  sameAsCustomer?: boolean;
}

export const useBookingStore = defineStore('booking', () => {
  const currentStep = ref<number>(0)
  const selectedLessonType = ref<LessonType | null>(null)
  const selectedDate = ref<string>('')
  const selectedTime = ref<string>('')

  // Customer/Contact Information
  const customerInfo = ref<GuestCustomer>({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
  })

  // Riders for this booking (can be 1 or more)
  const riders = ref<RiderInfo[]>([{
    firstName: '',
    lastName: '',
    dateOfBirth: '',
    sameAsCustomer: false,
  }])

  // Confirmation state
  const bookingConfirmed = ref<boolean>(false)
  const createdBookingId = ref<string>('')

  const totalSteps = 5

  const canGoNext = computed(() => {
    switch (currentStep.value) {
      case 0:
        // Lesson type selected
        return !!selectedLessonType.value
      case 1:
        // Date and time selected
        return !!selectedDate.value && !!selectedTime.value
      case 2:
        // Customer info complete and riders info complete
        return isCustomerInfoValid() && areRidersValid()
      case 3:
        // Review step - just show data, always can proceed
        return true
      case 4:
        // Confirmation - always can stay
        return true
      default:
        return false
    }
  })

  function isCustomerInfoValid(): boolean {
    return !!(customerInfo.value.firstName &&
              customerInfo.value.lastName &&
              customerInfo.value.email)
  }

  function areRidersValid(): boolean {
    // Every required rider needs a name and DOB; Rider 1 may use the contact name ("I am the rider").
    const required = selectedLessonType.value?.requiredRiderCount || 1
    return riders.value.length === required && riders.value.every((r, i) => {
      const usesContact = i === 0 && r.sameAsCustomer
      const firstName = usesContact ? customerInfo.value.firstName : r.firstName
      const lastName = usesContact ? customerInfo.value.lastName : r.lastName
      return !!(firstName?.trim() && lastName?.trim() && r.dateOfBirth)
    })
  }

  function getRiderCount(): number {
    return selectedLessonType.value?.requiredRiderCount || 1
  }

  const isLastStep = computed(() => currentStep.value === totalSteps - 1)

  function setStep(step: number) {
    if (step >= 0 && step < totalSteps) {
      currentStep.value = step
    }
  }

  function nextStep() {
    if (canGoNext.value && currentStep.value < totalSteps - 1) {
      currentStep.value++
    }
  }

  function prevStep() {
    if (currentStep.value > 0) {
      currentStep.value--
    }
  }

  function setLessonType(lesson: LessonType) {
    const riderCount = lesson.requiredRiderCount || 1
    if (selectedLessonType.value?.id !== lesson.id || riders.value.length !== riderCount) {
      selectedTime.value = ''
      // Reset riders to match new lesson requirements
      riders.value = Array(riderCount).fill(null).map(() => ({
        firstName: '',
        lastName: '',
        dateOfBirth: '',
        sameAsCustomer: false,
      }))
    }
    selectedLessonType.value = lesson
  }

  function setDateAndTime(date: string, time: string) {
    selectedDate.value = date
    selectedTime.value = time
  }

  function updateCustomerInfo(info: Partial<GuestCustomer>) {
    customerInfo.value = { ...customerInfo.value, ...info }
  }

  function updateRider(index: number, info: Partial<RiderInfo>) {
    if (index >= 0 && index < riders.value.length) {
      riders.value[index] = { ...riders.value[index], ...info }
    }
  }

  function setBookingConfirmed(bookingId: string) {
    bookingConfirmed.value = true
    createdBookingId.value = bookingId
  }

  function resetWizard() {
    currentStep.value = 0
    selectedLessonType.value = null
    selectedDate.value = ''
    selectedTime.value = ''
    customerInfo.value = {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
    }
    riders.value = [{
      firstName: '',
      lastName: '',
      dateOfBirth: '',
      sameAsCustomer: false,
    }]
    bookingConfirmed.value = false
    createdBookingId.value = ''
  }

  return {
    currentStep,
    selectedLessonType,
    selectedDate,
    selectedTime,
    customerInfo,
    riders,
    bookingConfirmed,
    createdBookingId,
    totalSteps,
    canGoNext,
    isLastStep,
    setStep,
    nextStep,
    prevStep,
    setLessonType,
    setDateAndTime,
    updateCustomerInfo,
    updateRider,
    setBookingConfirmed,
    resetWizard,
    isCustomerInfoValid,
    areRidersValid,
    getRiderCount,
  }
})
