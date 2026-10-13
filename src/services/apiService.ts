/**
 * API Service Layer
 *
 * Typed functions for the Laravel REST API (/api).
 * Used by adminStore and other parts of the application.
 *
 * The API returns database rows (snake_case); this layer maps them to the
 * camelCase models in @/types/models and keeps the same function signatures
 * the Supabase layer had (lists return [] and writes return false/null on
 * failure, after logging).
 */

import { api } from '@/lib/api'
import type { Customer, Rider, Horse, LessonType, InstructorAvailability, HorseRecurringAvailability, BlockedTime, Booking } from '@/types/models'

type Row = Record<string, any>

async function list<T>(path: string, map: (row: Row) => T, label: string): Promise<T[]> {
  const { data, error } = await api<Row[]>('GET', path)
  if (error) {
    console.error(`Error fetching ${label}:`, error)
    return []
  }
  return (data || []).map(map)
}

async function create<T>(path: string, body: Row, map: (row: Row) => T, label: string): Promise<T | null> {
  const { data, error } = await api<Row>('POST', path, body)
  if (error || !data) {
    console.error(`Error creating ${label}:`, error)
    return null
  }
  return map(data)
}

async function write(method: 'PATCH' | 'DELETE', path: string, body: Row | undefined, label: string): Promise<boolean> {
  const { error } = await api(method, path, body)
  if (error) {
    console.error(`Error ${method === 'DELETE' ? 'deleting' : 'updating'} ${label}:`, error)
    return false
  }
  return true
}

// ============================================================
// LESSON TYPES
// ============================================================

const toLessonType = (row: Row): LessonType => ({
  id: row.id,
  name: row.name,
  description: row.description,
  durationMinutes: row.duration_minutes,
  price: row.price,
  difficultyLevel: row.difficulty_level,
  maxGroupSize: row.max_group_size,
  requiredHorseCount: row.required_horse_count || 1,
  requiredRiderCount: row.required_rider_count || 1,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
})

export async function fetchLessonTypes(activeOnly = false): Promise<LessonType[]> {
  // Public visitors only ever see active lesson types; admins see all.
  return list(activeOnly ? '/lesson-types' : '/admin/lesson-types', toLessonType, 'lesson types')
}

export async function updateLessonType(id: string, updates: Partial<LessonType>): Promise<boolean> {
  return write('PATCH', `/admin/lesson-types/${encodeURIComponent(id)}`, {
    name: updates.name,
    description: updates.description,
    duration_minutes: updates.durationMinutes,
    price: updates.price,
    difficulty_level: updates.difficultyLevel,
    max_group_size: updates.maxGroupSize,
  }, 'lesson type')
}

// ============================================================
// HORSES
// ============================================================

const toHorse = (row: Row): Horse => ({
  id: row.id,
  name: row.name,
  breed: row.breed,
  age: row.age,
  color: row.color,
  height: row.height,
  temperament: row.temperament,
  experienceLevel: row.experience_level,
  active: row.active,
  notes: row.notes,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
  deletedAt: row.deleted_at,
})

const horseFields = (horse: Partial<Horse>): Row => ({
  name: horse.name,
  breed: horse.breed,
  age: horse.age,
  color: horse.color,
  height: horse.height,
  temperament: horse.temperament,
  experience_level: horse.experienceLevel,
  active: horse.active,
  notes: horse.notes,
})

export async function fetchHorses(includeInactive = true): Promise<Horse[]> {
  return list(`/admin/horses?include_inactive=${includeInactive ? 1 : 0}`, toHorse, 'horses')
}

export async function createHorse(horse: Omit<Horse, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Horse | null> {
  return create('/admin/horses', horseFields(horse), toHorse, 'horse')
}

export async function updateHorse(id: string, updates: Partial<Horse>): Promise<boolean> {
  return write('PATCH', `/admin/horses/${encodeURIComponent(id)}`, horseFields(updates), 'horse')
}

export async function deleteHorse(id: string): Promise<boolean> {
  return write('DELETE', `/admin/horses/${encodeURIComponent(id)}`, undefined, 'horse')
}

// ============================================================
// CUSTOMERS
// ============================================================

const toCustomer = (row: Row): Customer => ({
  id: row.id,
  authUserId: row.user_id == null ? undefined : String(row.user_id),
  firstName: row.first_name,
  lastName: row.last_name,
  email: row.email,
  phone: row.phone,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
  deletedAt: row.deleted_at,
})

const customerFields = (customer: Partial<Customer>): Row => ({
  first_name: customer.firstName,
  last_name: customer.lastName,
  email: customer.email,
  phone: customer.phone,
})

export async function fetchCustomers(): Promise<Customer[]> {
  return list('/admin/customers', toCustomer, 'customers')
}

export async function createCustomer(customer: Omit<Customer, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Customer | null> {
  return create('/admin/customers', customerFields(customer), toCustomer, 'customer')
}

export async function updateCustomer(id: string, updates: Partial<Customer>): Promise<boolean> {
  return write('PATCH', `/admin/customers/${encodeURIComponent(id)}`, customerFields(updates), 'customer')
}

// ============================================================
// RIDERS
// ============================================================

const toRider = (row: Row): Rider => ({
  id: row.id,
  customerId: row.customer_id,
  firstName: row.first_name,
  lastName: row.last_name,
  email: row.email,
  phone: row.phone,
  dateOfBirth: row.date_of_birth,
  address: row.address,
  emergencyContactName: row.emergency_contact_name,
  emergencyContactPhone: row.emergency_contact_phone,
  experienceLevel: row.riding_experience,
  notes: row.notes,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
  deletedAt: row.deleted_at,
})

const riderFields = (rider: Partial<Rider>): Row => ({
  customer_id: rider.customerId,
  first_name: rider.firstName,
  last_name: rider.lastName,
  email: rider.email,
  phone: rider.phone,
  date_of_birth: rider.dateOfBirth,
  address: rider.address,
  emergency_contact_name: rider.emergencyContactName,
  emergency_contact_phone: rider.emergencyContactPhone,
  riding_experience: rider.experienceLevel,
  notes: rider.notes,
})

export async function fetchRiders(): Promise<Rider[]> {
  return list('/admin/riders', toRider, 'riders')
}

export async function createRider(rider: Omit<Rider, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Rider | null> {
  return create('/admin/riders', riderFields(rider), toRider, 'rider')
}

export async function updateRider(id: string, updates: Partial<Rider>): Promise<boolean> {
  return write('PATCH', `/admin/riders/${encodeURIComponent(id)}`, riderFields(updates), 'rider')
}

export async function deleteRider(id: string): Promise<boolean> {
  return write('DELETE', `/admin/riders/${encodeURIComponent(id)}`, undefined, 'rider')
}

// ============================================================
// BOOKINGS
// ============================================================

export async function fetchBookings(): Promise<Booking[]> {
  return list('/admin/bookings', (row): Booking => ({
    id: row.id,
    customerId: row.customer_id,
    lessonTypeId: row.lesson_type_id,
    horseId: row.horse_id,
    startTime: row.start_time,
    endTime: row.end_time,
    status: row.status,
    bookingType: row.booking_type,
    recurringLessonId: row.recurring_lesson_id,
    version: row.version,
    notes: row.notes,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
    deletedAt: row.deleted_at,
    riderNames: row.rider_names || [],
  }), 'bookings')
}

// ============================================================
// AVAILABILITY
// ============================================================

const toInstructorAvailability = (row: Row): InstructorAvailability => ({
  id: row.id,
  dayOfWeek: row.day_of_week,
  startTime: row.start_time,
  endTime: row.end_time,
  active: row.active,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
})

export async function fetchInstructorAvailability(): Promise<InstructorAvailability[]> {
  return list('/admin/instructor-availability', toInstructorAvailability, 'instructor availability')
}

export async function updateInstructorAvailability(id: string, updates: Partial<InstructorAvailability>): Promise<boolean> {
  return write('PATCH', `/admin/instructor-availability/${encodeURIComponent(id)}`, {
    day_of_week: updates.dayOfWeek,
    start_time: updates.startTime,
    end_time: updates.endTime,
    active: updates.active,
  }, 'instructor availability')
}

export async function createInstructorAvailability(availability: Omit<InstructorAvailability, 'id' | 'createdAt' | 'updatedAt'>): Promise<InstructorAvailability | null> {
  return create('/admin/instructor-availability', {
    day_of_week: availability.dayOfWeek,
    start_time: availability.startTime,
    end_time: availability.endTime,
    active: availability.active ?? true,
  }, toInstructorAvailability, 'instructor availability')
}

export async function deleteInstructorAvailability(id: string): Promise<boolean> {
  return write('DELETE', `/admin/instructor-availability/${encodeURIComponent(id)}`, undefined, 'instructor availability')
}

const toHorseRecurringAvailability = (row: Row): HorseRecurringAvailability => ({
  id: row.id,
  horseId: row.horse_id,
  dayOfWeek: row.day_of_week,
  startTime: row.start_time,
  endTime: row.end_time,
  active: row.active,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
})

export async function fetchHorseRecurringAvailability(): Promise<HorseRecurringAvailability[]> {
  return list('/admin/horse-availability', toHorseRecurringAvailability, 'horse recurring availability')
}

export async function updateHorseRecurringAvailability(id: string, updates: Partial<HorseRecurringAvailability>): Promise<boolean> {
  return write('PATCH', `/admin/horse-availability/${encodeURIComponent(id)}`, {
    day_of_week: updates.dayOfWeek,
    start_time: updates.startTime,
    end_time: updates.endTime,
    active: updates.active,
  }, 'horse recurring availability')
}

export async function createHorseRecurringAvailability(availability: Omit<HorseRecurringAvailability, 'id' | 'createdAt' | 'updatedAt'>): Promise<HorseRecurringAvailability | null> {
  return create('/admin/horse-availability', {
    horse_id: availability.horseId,
    day_of_week: availability.dayOfWeek,
    start_time: availability.startTime,
    end_time: availability.endTime,
    active: availability.active ?? true,
  }, toHorseRecurringAvailability, 'horse recurring availability')
}

export async function deleteHorseRecurringAvailability(id: string): Promise<boolean> {
  return write('DELETE', `/admin/horse-availability/${encodeURIComponent(id)}`, undefined, 'horse recurring availability')
}

const toBlockedTime = (row: Row): BlockedTime => ({
  id: row.id,
  horseId: row.horse_id,
  date: row.date,
  startTime: row.start_time,
  endTime: row.end_time,
  reason: row.reason,
  createdAt: row.created_at,
  updatedAt: row.updated_at,
})

export async function fetchBlockedTimes(): Promise<BlockedTime[]> {
  return list('/admin/blocked-times', toBlockedTime, 'blocked times')
}

export async function createBlockedTime(blockedTime: Omit<BlockedTime, 'id' | 'createdAt' | 'updatedAt'>): Promise<BlockedTime | null> {
  return create('/admin/blocked-times', {
    horse_id: blockedTime.horseId,
    date: blockedTime.date,
    start_time: blockedTime.startTime,
    end_time: blockedTime.endTime,
    reason: blockedTime.reason,
  }, toBlockedTime, 'blocked time')
}

export async function deleteBlockedTime(id: string): Promise<boolean> {
  return write('DELETE', `/admin/blocked-times/${encodeURIComponent(id)}`, undefined, 'blocked time')
}

// ============================================================
// BUSINESS CONFIG
// ============================================================

export async function fetchBusinessTimezone(): Promise<string | null> {
  const { data } = await api<{ business_timezone: string }>('GET', '/config')
  return data?.business_timezone ?? null
}
