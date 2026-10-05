import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Customer, Rider, Horse, Booking, LessonType, InstructorAvailability, HorseRecurringAvailability, BlockedTime } from '@/types/models'
import * as supabaseService from '@/services/supabaseService'

/**
 * Admin Store - Phase 2C
 *
 * Manages admin application state with real Supabase persistence.
 * All CRUD operations persist to PostgreSQL via Supabase service layer.
 */
export const useAdminStore = defineStore('admin', () => {
  // Data from Supabase
  const customers = ref<Customer[]>([])
  const riders = ref<Rider[]>([])
  const horses = ref<Horse[]>([])
  const lessonTypes = ref<LessonType[]>([])
  const bookings = ref<Booking[]>([])
  const instructorAvailability = ref<InstructorAvailability[]>([])
  const horseRecurringAvailability = ref<HorseRecurringAvailability[]>([])
  const blockedTimes = ref<BlockedTime[]>([])

  // Loading and error states
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const hasError = computed(() => !!error.value)

  // Initialize: Load all data from Supabase
  async function loadAllData() {
    isLoading.value = true
    error.value = null

    try {
      const [
        customersData,
        ridersData,
        horsesData,
        lessonTypesData,
        bookingsData,
        instAvailData,
        horseAvailData,
        blockedTimesData,
      ] = await Promise.all([
        supabaseService.fetchCustomers(),
        supabaseService.fetchRiders(),
        supabaseService.fetchHorses(true),
        supabaseService.fetchLessonTypes(false),
        supabaseService.fetchBookings(),
        supabaseService.fetchInstructorAvailability(),
        supabaseService.fetchHorseRecurringAvailability(),
        supabaseService.fetchBlockedTimes(),
      ])

      customers.value = customersData
      riders.value = ridersData
      horses.value = horsesData
      lessonTypes.value = lessonTypesData
      bookings.value = bookingsData
      instructorAvailability.value = instAvailData
      horseRecurringAvailability.value = horseAvailData
      blockedTimes.value = blockedTimesData
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load data from Supabase'
      console.error('Error loading admin data:', err)
    } finally {
      isLoading.value = false
    }
  }

  // ============================================================
  // Customers
  // ============================================================
  async function addCustomer(customer: Omit<Customer, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>) {
    const newCustomer = await supabaseService.createCustomer(customer)
    if (newCustomer) {
      customers.value.push(newCustomer)
      return newCustomer
    }
    error.value = 'Failed to create customer'
    return null
  }

  async function updateCustomerLocal(id: string, updates: Partial<Customer>) {
    const success = await supabaseService.updateCustomer(id, updates)
    if (success) {
      const index = customers.value.findIndex(c => c.id === id)
      if (index !== -1) {
        customers.value[index] = {
          ...customers.value[index],
          ...updates,
          updatedAt: new Date().toISOString(),
        }
      }
      return true
    }
    error.value = 'Failed to update customer'
    return false
  }

  function getCustomer(id: string) {
    return customers.value.find(c => c.id === id)
  }

  // ============================================================
  // Riders
  // ============================================================
  async function addRider(rider: Omit<Rider, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>) {
    const newRider = await supabaseService.createRider(rider)
    if (newRider) {
      riders.value.push(newRider)
      return newRider
    }
    error.value = 'Failed to create rider'
    return null
  }

  async function updateRiderLocal(id: string, updates: Partial<Rider>) {
    const success = await supabaseService.updateRider(id, updates)
    if (success) {
      const index = riders.value.findIndex(r => r.id === id)
      if (index !== -1) {
        riders.value[index] = {
          ...riders.value[index],
          ...updates,
          updatedAt: new Date().toISOString(),
        }
      }
      return true
    }
    error.value = 'Failed to update rider'
    return false
  }

  function getRider(id: string) {
    return riders.value.find(r => r.id === id)
  }

  async function deleteRider(id: string) {
    const success = await supabaseService.deleteRider(id)
    if (success) {
      const index = riders.value.findIndex(r => r.id === id)
      if (index !== -1) {
        riders.value.splice(index, 1)
      }
      return true
    }
    error.value = 'Failed to delete rider'
    return false
  }

  // ============================================================
  // Horses
  // ============================================================
  async function addHorse(horse: Omit<Horse, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>) {
    const newHorse = await supabaseService.createHorse(horse)
    if (newHorse) {
      horses.value.push(newHorse)
      return newHorse
    }
    error.value = 'Failed to create horse'
    return null
  }

  async function updateHorseLocal(id: string, updates: Partial<Horse>) {
    const success = await supabaseService.updateHorse(id, updates)
    if (success) {
      const index = horses.value.findIndex(h => h.id === id)
      if (index !== -1) {
        horses.value[index] = {
          ...horses.value[index],
          ...updates,
          updatedAt: new Date().toISOString(),
        }
      }
      return true
    }
    error.value = 'Failed to update horse'
    return false
  }

  function getHorse(id: string) {
    return horses.value.find(h => h.id === id)
  }

  async function deleteHorse(id: string) {
    const success = await supabaseService.deleteHorse(id)
    if (success) {
      const index = horses.value.findIndex(h => h.id === id)
      if (index !== -1) {
        horses.value.splice(index, 1)
      }
      return true
    }
    error.value = 'Failed to delete horse'
    return false
  }

  // ============================================================
  // Bookings (read-only for now)
  // ============================================================
  function getBooking(id: string) {
    return bookings.value.find(b => b.id === id)
  }

  function getBookingsForDate(date: string) {
    return bookings.value.filter(b => b.startTime.split('T')[0] === date)
  }

  function getUpcomingBookings(days: number = 7) {
    const today = new Date()
    const futureDate = new Date()
    futureDate.setDate(today.getDate() + days)

    return bookings.value.filter(b => {
      const bookingDate = new Date(b.startTime)
      return bookingDate >= today && bookingDate <= futureDate
    })
  }

  // ============================================================
  // Availability
  // ============================================================
  async function updateInstructorAvailabilityLocal(id: string, updates: Partial<InstructorAvailability>) {
    const success = await supabaseService.updateInstructorAvailability(id, updates)
    if (success) {
      const index = instructorAvailability.value.findIndex(a => a.id === id)
      if (index !== -1) {
        instructorAvailability.value[index] = {
          ...instructorAvailability.value[index],
          ...updates,
          updatedAt: new Date().toISOString(),
        }
      }
      return true
    }
    error.value = 'Failed to update instructor availability'
    return false
  }

  async function updateHorseAvailabilityLocal(id: string, updates: Partial<HorseRecurringAvailability>) {
    const success = await supabaseService.updateHorseRecurringAvailability(id, updates)
    if (success) {
      const index = horseRecurringAvailability.value.findIndex(a => a.id === id)
      if (index !== -1) {
        horseRecurringAvailability.value[index] = {
          ...horseRecurringAvailability.value[index],
          ...updates,
          updatedAt: new Date().toISOString(),
        }
      }
      return true
    }
    error.value = 'Failed to update horse availability'
    return false
  }

  async function addBlockedTime(blockedTime: Omit<BlockedTime, 'id' | 'createdAt' | 'updatedAt'>) {
    const newBlockedTime = await supabaseService.createBlockedTime(blockedTime)
    if (newBlockedTime) {
      blockedTimes.value.push(newBlockedTime)
      return newBlockedTime
    }
    error.value = 'Failed to create blocked time'
    return null
  }

  async function deleteBlockedTime(id: string) {
    const success = await supabaseService.deleteBlockedTime(id)
    if (success) {
      const index = blockedTimes.value.findIndex(b => b.id === id)
      if (index !== -1) {
        blockedTimes.value.splice(index, 1)
      }
      return true
    }
    error.value = 'Failed to delete blocked time'
    return false
  }

  return {
    // State
    customers,
    riders,
    horses,
    lessonTypes,
    bookings,
    instructorAvailability,
    horseRecurringAvailability,
    blockedTimes,
    isLoading,
    error,
    hasError,

    // Data loading
    loadAllData,

    // Customer operations
    addCustomer,
    updateCustomerLocal,
    getCustomer,

    // Rider operations
    addRider,
    updateRiderLocal,
    getRider,
    deleteRider,

    // Horse operations
    addHorse,
    updateHorseLocal,
    getHorse,
    deleteHorse,

    // Booking queries (read-only)
    getBooking,
    getBookingsForDate,
    getUpcomingBookings,

    // Availability operations
    updateInstructorAvailabilityLocal,
    updateHorseAvailabilityLocal,
    addBlockedTime,
    deleteBlockedTime,
  }
})
