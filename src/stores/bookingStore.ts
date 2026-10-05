import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { BookingWizardState, LessonType, Rider } from '@/types/models'

export const useBookingStore = defineStore('booking', () => {
  const currentStep = ref<number>(0)
  const selectedLessonType = ref<LessonType | null>(null)
  const selectedDate = ref<string>('')
  const selectedTime = ref<string>('')
  const selectedHorseId = ref<string>('')
  const riderInfo = ref<Partial<Rider>>({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    experienceLevel: 'beginner',
  })
  const waiverSigned = ref<boolean>(false)

  const totalSteps = 5

  const canGoNext = computed(() => {
    switch (currentStep.value) {
      case 0:
        return !!selectedLessonType.value
      case 1:
        return !!selectedDate.value && !!selectedTime.value
      case 2:
        return !!riderInfo.value.firstName &&
               !!riderInfo.value.lastName &&
               !!riderInfo.value.email
      case 3:
        return waiverSigned.value
      case 4:
        return true
      default:
        return false
    }
  })

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
    selectedLessonType.value = lesson
  }

  function setDateAndTime(date: string, time: string) {
    selectedDate.value = date
    selectedTime.value = time
  }

  function setHorseId(horseId: string) {
    selectedHorseId.value = horseId
  }

  function updateRiderInfo(info: Partial<Rider>) {
    riderInfo.value = { ...riderInfo.value, ...info }
  }

  function setWaiverSigned(signed: boolean) {
    waiverSigned.value = signed
  }

  function submitBooking() {
    // In Phase 1, this just logs the booking data
    const bookingData = {
      lessonType: selectedLessonType.value,
      date: selectedDate.value,
      time: selectedTime.value,
      horse: selectedHorseId.value,
      rider: riderInfo.value,
      waiverSigned: waiverSigned.value,
      submittedAt: new Date().toISOString(),
    }
    console.log('Booking submitted:', bookingData)
    return bookingData
  }

  function resetWizard() {
    currentStep.value = 0
    selectedLessonType.value = null
    selectedDate.value = ''
    selectedTime.value = ''
    selectedHorseId.value = ''
    riderInfo.value = {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      dateOfBirth: '',
      experienceLevel: 'beginner',
    }
    waiverSigned.value = false
  }

  return {
    currentStep,
    selectedLessonType,
    selectedDate,
    selectedTime,
    selectedHorseId,
    riderInfo,
    waiverSigned,
    totalSteps,
    canGoNext,
    isLastStep,
    setStep,
    nextStep,
    prevStep,
    setLessonType,
    setDateAndTime,
    setHorseId,
    updateRiderInfo,
    setWaiverSigned,
    submitBooking,
    resetWizard,
  }
})
