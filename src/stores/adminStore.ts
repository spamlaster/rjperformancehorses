import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Customer, Rider, Horse, Booking, LessonType } from '@/types/models'
import {
  mockCustomers,
  mockRiders,
  mockHorses,
  mockLessonTypes,
  mockBookings,
} from '@/data/mockData'

export const useAdminStore = defineStore('admin', () => {
  const customers = ref<Customer[]>([...mockCustomers])
  const riders = ref<Rider[]>([...mockRiders])
  const horses = ref<Horse[]>([...mockHorses])
  const lessonTypes = ref<LessonType[]>([...mockLessonTypes])
  const bookings = ref<Booking[]>([...mockBookings])

  // Riders CRUD
  function addRider(rider: Omit<Rider, 'id' | 'createdAt' | 'updatedAt'>) {
    const newRider: Rider = {
      ...rider,
      id: `rider-${Date.now()}`,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    riders.value.push(newRider)
    return newRider
  }

  function updateRider(id: string, updates: Partial<Rider>) {
    const index = riders.value.findIndex(r => r.id === id)
    if (index !== -1) {
      riders.value[index] = {
        ...riders.value[index],
        ...updates,
        updatedAt: new Date().toISOString(),
      }
    }
  }

  function deleteRider(id: string) {
    riders.value = riders.value.filter(r => r.id !== id)
  }

  function getRider(id: string) {
    return riders.value.find(r => r.id === id)
  }

  // Horses CRUD
  function addHorse(horse: Omit<Horse, 'id' | 'createdAt' | 'updatedAt'>) {
    const newHorse: Horse = {
      ...horse,
      id: `horse-${Date.now()}`,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    horses.value.push(newHorse)
    return newHorse
  }

  function updateHorse(id: string, updates: Partial<Horse>) {
    const index = horses.value.findIndex(h => h.id === id)
    if (index !== -1) {
      horses.value[index] = {
        ...horses.value[index],
        ...updates,
        updatedAt: new Date().toISOString(),
      }
    }
  }

  function deleteHorse(id: string) {
    horses.value = horses.value.filter(h => h.id !== id)
  }

  function getHorse(id: string) {
    return horses.value.find(h => h.id === id)
  }

  // Bookings CRUD
  function addBooking(booking: Omit<Booking, 'id' | 'createdAt' | 'updatedAt'>) {
    const newBooking: Booking = {
      ...booking,
      id: `booking-${Date.now()}`,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    bookings.value.push(newBooking)
    return newBooking
  }

  function updateBooking(id: string, updates: Partial<Booking>) {
    const index = bookings.value.findIndex(b => b.id === id)
    if (index !== -1) {
      bookings.value[index] = {
        ...bookings.value[index],
        ...updates,
        updatedAt: new Date().toISOString(),
      }
    }
  }

  function deleteBooking(id: string) {
    bookings.value = bookings.value.filter(b => b.id !== id)
  }

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

  // Customers
  function addCustomer(customer: Omit<Customer, 'id' | 'createdAt' | 'updatedAt'>) {
    const newCustomer: Customer = {
      ...customer,
      id: `customer-${Date.now()}`,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    customers.value.push(newCustomer)
    return newCustomer
  }

  function getCustomer(id: string) {
    return customers.value.find(c => c.id === id)
  }

  // Note: Availability queries and blocking will be implemented in Phase 2
  // They will use instructor_availability, horse_recurring_availability, and blocked_times tables
  function getAvailableSlots(date: string) {
    // Phase 1: Return empty array as placeholder
    // Phase 2: Will query database for available times based on:
    // - instructor_availability
    // - horse_recurring_availability
    // - blocked_times exceptions
    // - existing bookings
    return []
  }

  return {
    customers,
    riders,
    horses,
    lessonTypes,
    bookings,
    addCustomer,
    getCustomer,
    addRider,
    updateRider,
    deleteRider,
    getRider,
    addHorse,
    updateHorse,
    deleteHorse,
    getHorse,
    addBooking,
    updateBooking,
    deleteBooking,
    getBooking,
    getBookingsForDate,
    getUpcomingBookings,
    getAvailableSlots,
  }
})
