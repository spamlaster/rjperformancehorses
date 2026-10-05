import { Rider, Horse, LessonType, TimeSlot, Booking, Waiver } from '@/types/models'
import { addDays, format } from 'date-fns'

export const mockLessonTypes: LessonType[] = [
  {
    id: 'lesson-1',
    name: 'Walk & Trot',
    description: 'Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.',
    durationMinutes: 30,
    price: 45,
    difficultyLevel: 'beginner',
    maxGroupSize: 1,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'lesson-2',
    name: 'Intermediate Riding',
    description: 'Build on fundamentals with canter and basic jumping.',
    durationMinutes: 45,
    price: 55,
    difficultyLevel: 'intermediate',
    maxGroupSize: 1,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'lesson-3',
    name: 'Advanced Horsemanship',
    description: 'Advanced techniques and competition preparation.',
    durationMinutes: 60,
    price: 65,
    difficultyLevel: 'advanced',
    maxGroupSize: 1,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'lesson-4',
    name: 'Sibling/Joined Lesson',
    description: 'Two riders learning together on separate horses.',
    durationMinutes: 60,
    price: 100,
    difficultyLevel: 'beginner',
    maxGroupSize: 2,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'lesson-5',
    name: 'Barrel Racing',
    description: 'Specialized training for barrel racing techniques.',
    durationMinutes: 60,
    price: 75,
    difficultyLevel: 'intermediate',
    maxGroupSize: 1,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'lesson-6',
    name: 'Pole Bending',
    description: 'Learn precise weaving patterns and speed control.',
    durationMinutes: 60,
    price: 75,
    difficultyLevel: 'intermediate',
    maxGroupSize: 1,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]

export const mockHorses: Horse[] = [
  {
    id: 'horse-1',
    name: 'Sunset',
    breed: 'Quarter Horse',
    age: 8,
    color: 'Bay',
    height: '15.2hh',
    temperament: 'Gentle and steady, great for beginners',
    experienceLevel: 'beginner_friendly',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'horse-2',
    name: 'Ranger',
    breed: 'Quarter Horse',
    age: 12,
    color: 'Palomino',
    height: '15.0hh',
    temperament: 'Calm and experienced, perfect for lessons',
    experienceLevel: 'beginner_friendly',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'horse-3',
    name: 'Dakota',
    breed: 'Paint',
    age: 6,
    color: 'Tobiano',
    height: '14.3hh',
    temperament: 'Energetic and responsive',
    experienceLevel: 'intermediate',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'horse-4',
    name: 'Thunder',
    breed: 'Quarter Horse',
    age: 10,
    color: 'Chestnut',
    height: '15.1hh',
    temperament: 'Athletic and focused',
    experienceLevel: 'advanced',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'horse-5',
    name: 'Moonlight',
    breed: 'Appaloosa',
    age: 7,
    color: 'Spotted',
    height: '14.2hh',
    temperament: 'Sweet and spirited',
    experienceLevel: 'intermediate',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'horse-6',
    name: 'Spirit',
    breed: 'Mustang',
    age: 5,
    color: 'Bay',
    height: '15.0hh',
    temperament: 'Quick learner, barrel racing specialist',
    experienceLevel: 'advanced',
    available: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]

export const mockRiders: Rider[] = [
  {
    id: 'rider-1',
    firstName: 'Sarah',
    lastName: 'Johnson',
    email: 'sarah@example.com',
    phone: '555-123-4567',
    dateOfBirth: '1995-06-15',
    experienceLevel: 'intermediate',
    address: '123 Main St, Town, State',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'rider-2',
    firstName: 'Emma',
    lastName: 'Jensen',
    email: 'emma@example.com',
    phone: '555-234-5678',
    dateOfBirth: '2010-03-22',
    experienceLevel: 'beginner',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'rider-3',
    firstName: 'Hailey',
    lastName: 'Jensen',
    email: 'hailey@example.com',
    phone: '555-234-5678',
    dateOfBirth: '2012-08-10',
    experienceLevel: 'beginner',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'rider-4',
    firstName: 'Michael',
    lastName: 'Brown',
    email: 'michael@example.com',
    phone: '555-345-6789',
    dateOfBirth: '1988-11-05',
    experienceLevel: 'advanced',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'rider-5',
    firstName: 'Ava',
    lastName: 'Smith',
    email: 'ava@example.com',
    phone: '555-456-7890',
    dateOfBirth: '2008-01-18',
    experienceLevel: 'intermediate',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]

export const generateMockTimeSlots = (): TimeSlot[] => {
  const slots: TimeSlot[] = []
  const startDate = new Date()
  const horseIds = mockHorses.map(h => h.id)

  for (let dayOffset = 0; dayOffset < 30; dayOffset++) {
    const date = format(addDays(startDate, dayOffset), 'yyyy-MM-dd')
    const dayOfWeek = new Date(date).getDay()

    if (dayOfWeek === 0 || dayOfWeek === 6) continue // Skip weekends

    horseIds.forEach((horseid, index) => {
      const baseHour = 14 // 2 PM
      const slotCount = 2 + (index % 2)

      for (let i = 0; i < slotCount; i++) {
        const startHour = baseHour + i
        const startTime = `${String(startHour).padStart(2, '0')}:00`
        const endTime = `${String(startHour + 1).padStart(2, '0')}:00`

        if (startHour < 18) {
          slots.push({
            id: `slot-${date}-${horseid}-${i}`,
            horseid,
            date,
            startTime,
            endTime,
            status: 'available',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
          })
        }
      }
    })
  }

  return slots
}

export const mockBookings: Booking[] = [
  {
    id: 'booking-1',
    lessonTypeId: 'lesson-1',
    riderId: 'rider-1',
    horseid: 'horse-1',
    startTime: addDays(new Date(), 1).toISOString(),
    endTime: addDays(new Date(), 1).toISOString(),
    status: 'confirmed',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]

export const mockWaivers: Waiver[] = [
  {
    id: 'waiver-1',
    riderId: 'rider-1',
    waiverVersion: 1,
    isSigned: true,
    signedAt: new Date().toISOString(),
    signerName: 'Sarah Johnson',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]
