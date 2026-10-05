import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Rider, Horse, Booking, TimeSlot, LessonType } from '@/types/models'
import {
  mockRiders,
  mockHorses,
  mockLessonTypes,
  mockBookings,
  generateMockTimeSlots,
} from '@/data/mockData'

export const useAdminStore = defineStore('admin', () => {
  const riders = ref<Rider[]>([...mockRiders])
  const horses = ref<Horse[]>([...mockHorses])
  const lessonTypes = ref<LessonType[]>([...mockLessonTypes])
  const bookings = ref<Booking[]>([...mockBookings])
  const timeSlots = ref<TimeSlot[]>(generateMockTimeSlots())

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

  // Time Slots
  function getAvailableSlots(date: string) {
    return timeSlots.value.filter(
      slot => slot.date === date && slot.status === 'available'
    )
  }

  function blockTimeSlot(slotId: string) {
    const slot = timeSlots.value.find(s => s.id === slotId)
    if (slot) {
      slot.status = 'maintenance'
      slot.updatedAt = new Date().toISOString()
    }
  }

  function unblockTimeSlot(slotId: string) {
    const slot = timeSlots.value.find(s => s.id === slotId)
    if (slot && slot.status === 'maintenance') {
      slot.status = 'available'
      slot.updatedAt = new Date().toISOString()
    }
  }

  return {
    riders,
    horses,
    lessonTypes,
    bookings,
    timeSlots,
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
    blockTimeSlot,
    unblockTimeSlot,
  }
})
