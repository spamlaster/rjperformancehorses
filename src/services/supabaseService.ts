/**
 * Supabase Database Service Layer
 *
 * Provides typed, reusable functions for database operations.
 * Used by adminStore and other parts of the application.
 *
 * This layer:
 * - Encapsulates all Supabase queries
 * - Provides consistent error handling
 * - Returns typed results
 * - Prevents N+1 queries where practical
 */

import { supabase } from '@/lib/supabase'
import type { Customer, Rider, Horse, LessonType, InstructorAvailability, HorseRecurringAvailability, BlockedTime, Booking } from '@/types/models'

// ============================================================
// LESSON TYPES
// ============================================================

export async function fetchLessonTypes(activeOnly = false): Promise<LessonType[]> {
  let query = supabase
    .from('lesson_types')
    .select('*')

  if (activeOnly) {
    query = query.eq('active', true)
  }

  const { data, error } = await query

  if (error) {
    console.error('Error fetching lesson types:', error)
    return []
  }

  return (data || []).map(row => ({
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
  }))
}

export async function updateLessonType(id: string, updates: Partial<LessonType>): Promise<boolean> {
  const { error } = await supabase
    .from('lesson_types')
    .update({
      name: updates.name,
      description: updates.description,
      duration_minutes: updates.durationMinutes,
      price: updates.price,
      difficulty_level: updates.difficultyLevel,
      max_group_size: updates.maxGroupSize,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating lesson type:', error)
    return false
  }

  return true
}

// ============================================================
// HORSES
// ============================================================

export async function fetchHorses(includeInactive = true): Promise<Horse[]> {
  let query = supabase
    .from('horses')
    .select('*')

  if (!includeInactive) {
    query = query.eq('active', true)
  }

  const { data, error } = await query

  if (error) {
    console.error('Error fetching horses:', error)
    return []
  }

  return (data || []).map(row => ({
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
  }))
}

export async function createHorse(horse: Omit<Horse, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Horse | null> {
  const { data, error } = await supabase
    .from('horses')
    .insert({
      id: `horse-${Date.now()}`,
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
    .select()
    .single()

  if (error) {
    console.error('Error creating horse:', error)
    return null
  }

  return {
    id: data.id,
    name: data.name,
    breed: data.breed,
    age: data.age,
    color: data.color,
    height: data.height,
    temperament: data.temperament,
    experienceLevel: data.experience_level,
    active: data.active,
    notes: data.notes,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
    deletedAt: data.deleted_at,
  }
}

export async function updateHorse(id: string, updates: Partial<Horse>): Promise<boolean> {
  const { error } = await supabase
    .from('horses')
    .update({
      name: updates.name,
      breed: updates.breed,
      age: updates.age,
      color: updates.color,
      height: updates.height,
      temperament: updates.temperament,
      experience_level: updates.experienceLevel,
      active: updates.active,
      notes: updates.notes,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating horse:', error)
    return false
  }

  return true
}

export async function deleteHorse(id: string): Promise<boolean> {
  const { error } = await supabase
    .from('horses')
    .delete()
    .eq('id', id)

  if (error) {
    console.error('Error deleting horse:', error)
    return false
  }

  return true
}

// ============================================================
// CUSTOMERS
// ============================================================

export async function fetchCustomers(): Promise<Customer[]> {
  const { data, error } = await supabase
    .from('customers')
    .select('*')

  if (error) {
    console.error('Error fetching customers:', error)
    return []
  }

  return (data || []).map(row => ({
    id: row.id,
    authUserId: row.auth_user_id,
    firstName: row.first_name,
    lastName: row.last_name,
    email: row.email,
    phone: row.phone,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
    deletedAt: row.deleted_at,
  }))
}

export async function createCustomer(customer: Omit<Customer, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Customer | null> {
  const { data, error } = await supabase
    .from('customers')
    .insert({
      id: `customer-${Date.now()}`,
      first_name: customer.firstName,
      last_name: customer.lastName,
      email: customer.email,
      phone: customer.phone,
    })
    .select()
    .single()

  if (error) {
    console.error('Error creating customer:', error)
    return null
  }

  return {
    id: data.id,
    authUserId: data.auth_user_id,
    firstName: data.first_name,
    lastName: data.last_name,
    email: data.email,
    phone: data.phone,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
    deletedAt: data.deleted_at,
  }
}

export async function updateCustomer(id: string, updates: Partial<Customer>): Promise<boolean> {
  const { error } = await supabase
    .from('customers')
    .update({
      first_name: updates.firstName,
      last_name: updates.lastName,
      email: updates.email,
      phone: updates.phone,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating customer:', error)
    return false
  }

  return true
}

// ============================================================
// RIDERS
// ============================================================

export async function fetchRiders(): Promise<Rider[]> {
  const { data, error } = await supabase
    .from('riders')
    .select('*')

  if (error) {
    console.error('Error fetching riders:', error)
    return []
  }

  return (data || []).map(row => ({
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
  }))
}

export async function createRider(rider: Omit<Rider, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>): Promise<Rider | null> {
  const { data, error } = await supabase
    .from('riders')
    .insert({
      id: `rider-${Date.now()}`,
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
    .select()
    .single()

  if (error) {
    console.error('Error creating rider:', error)
    return null
  }

  return {
    id: data.id,
    customerId: data.customer_id,
    firstName: data.first_name,
    lastName: data.last_name,
    email: data.email,
    phone: data.phone,
    dateOfBirth: data.date_of_birth,
    address: data.address,
    emergencyContactName: data.emergency_contact_name,
    emergencyContactPhone: data.emergency_contact_phone,
    experienceLevel: data.riding_experience,
    notes: data.notes,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
    deletedAt: data.deleted_at,
  }
}

export async function updateRider(id: string, updates: Partial<Rider>): Promise<boolean> {
  const { error } = await supabase
    .from('riders')
    .update({
      customer_id: updates.customerId,
      first_name: updates.firstName,
      last_name: updates.lastName,
      email: updates.email,
      phone: updates.phone,
      date_of_birth: updates.dateOfBirth,
      address: updates.address,
      emergency_contact_name: updates.emergencyContactName,
      emergency_contact_phone: updates.emergencyContactPhone,
      riding_experience: updates.experienceLevel,
      notes: updates.notes,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating rider:', error)
    return false
  }

  return true
}

export async function deleteRider(id: string): Promise<boolean> {
  const { error } = await supabase
    .from('riders')
    .delete()
    .eq('id', id)

  if (error) {
    console.error('Error deleting rider:', error)
    return false
  }

  return true
}

// ============================================================
// BOOKINGS
// ============================================================

export async function fetchBookings(): Promise<Booking[]> {
  const { data, error } = await supabase
    .from('bookings')
    .select('*')

  if (error) {
    console.error('Error fetching bookings:', error)
    return []
  }

  return (data || []).map(row => ({
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
  }))
}

// ============================================================
// AVAILABILITY
// ============================================================

export async function fetchInstructorAvailability(): Promise<InstructorAvailability[]> {
  const { data, error } = await supabase
    .from('instructor_availability')
    .select('*')

  if (error) {
    console.error('Error fetching instructor availability:', error)
    return []
  }

  return (data || []).map(row => ({
    id: row.id,
    dayOfWeek: row.day_of_week,
    startTime: row.start_time,
    endTime: row.end_time,
    active: row.active,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  }))
}

export async function updateInstructorAvailability(id: string, updates: Partial<InstructorAvailability>): Promise<boolean> {
  const { error } = await supabase
    .from('instructor_availability')
    .update({
      day_of_week: updates.dayOfWeek,
      start_time: updates.startTime,
      end_time: updates.endTime,
      active: updates.active,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating instructor availability:', error)
    return false
  }

  return true
}

export async function createInstructorAvailability(availability: Omit<InstructorAvailability, 'id' | 'createdAt' | 'updatedAt'>): Promise<InstructorAvailability | null> {
  const { data, error } = await supabase
    .from('instructor_availability')
    .insert({
      id: `ia-${Date.now()}`,
      day_of_week: availability.dayOfWeek,
      start_time: availability.startTime,
      end_time: availability.endTime,
      active: availability.active ?? true,
    })
    .select()
    .single()

  if (error) {
    console.error('Error creating instructor availability:', error)
    return null
  }

  return {
    id: data.id,
    dayOfWeek: data.day_of_week,
    startTime: data.start_time,
    endTime: data.end_time,
    active: data.active,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
  }
}

export async function deleteInstructorAvailability(id: string): Promise<boolean> {
  const { error } = await supabase
    .from('instructor_availability')
    .delete()
    .eq('id', id)

  if (error) {
    console.error('Error deleting instructor availability:', error)
    return false
  }

  return true
}

export async function fetchHorseRecurringAvailability(): Promise<HorseRecurringAvailability[]> {
  const { data, error } = await supabase
    .from('horse_recurring_availability')
    .select('*')

  if (error) {
    console.error('Error fetching horse recurring availability:', error)
    return []
  }

  return (data || []).map(row => ({
    id: row.id,
    horseId: row.horse_id,
    dayOfWeek: row.day_of_week,
    startTime: row.start_time,
    endTime: row.end_time,
    active: row.active,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  }))
}

export async function updateHorseRecurringAvailability(id: string, updates: Partial<HorseRecurringAvailability>): Promise<boolean> {
  const { error } = await supabase
    .from('horse_recurring_availability')
    .update({
      day_of_week: updates.dayOfWeek,
      start_time: updates.startTime,
      end_time: updates.endTime,
      active: updates.active,
      updated_at: new Date().toISOString(),
    })
    .eq('id', id)

  if (error) {
    console.error('Error updating horse recurring availability:', error)
    return false
  }

  return true
}

export async function createHorseRecurringAvailability(availability: Omit<HorseRecurringAvailability, 'id' | 'createdAt' | 'updatedAt'>): Promise<HorseRecurringAvailability | null> {
  const { data, error } = await supabase
    .from('horse_recurring_availability')
    .insert({
      id: `hra-${Date.now()}`,
      horse_id: availability.horseId,
      day_of_week: availability.dayOfWeek,
      start_time: availability.startTime,
      end_time: availability.endTime,
      active: availability.active ?? true,
    })
    .select()
    .single()

  if (error) {
    console.error('Error creating horse recurring availability:', error)
    return null
  }

  return {
    id: data.id,
    horseId: data.horse_id,
    dayOfWeek: data.day_of_week,
    startTime: data.start_time,
    endTime: data.end_time,
    active: data.active,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
  }
}

export async function deleteHorseRecurringAvailability(id: string): Promise<boolean> {
  const { error } = await supabase
    .from('horse_recurring_availability')
    .delete()
    .eq('id', id)

  if (error) {
    console.error('Error deleting horse recurring availability:', error)
    return false
  }

  return true
}

export async function fetchBlockedTimes(): Promise<BlockedTime[]> {
  const { data, error } = await supabase
    .from('blocked_times')
    .select('*')

  if (error) {
    console.error('Error fetching blocked times:', error)
    return []
  }

  return (data || []).map(row => ({
    id: row.id,
    horseId: row.horse_id,
    date: row.date,
    startTime: row.start_time,
    endTime: row.end_time,
    reason: row.reason,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  }))
}

export async function createBlockedTime(blockedTime: Omit<BlockedTime, 'id' | 'createdAt' | 'updatedAt'>): Promise<BlockedTime | null> {
  const { data, error } = await supabase
    .from('blocked_times')
    .insert({
      id: `bt-${Date.now()}`,
      horse_id: blockedTime.horseId,
      date: blockedTime.date,
      start_time: blockedTime.startTime,
      end_time: blockedTime.endTime,
      reason: blockedTime.reason,
    })
    .select()
    .single()

  if (error) {
    console.error('Error creating blocked time:', error)
    return null
  }

  return {
    id: data.id,
    horseId: data.horse_id,
    date: data.date,
    startTime: data.start_time,
    endTime: data.end_time,
    reason: data.reason,
    createdAt: data.created_at,
    updatedAt: data.updated_at,
  }
}

export async function deleteBlockedTime(id: string): Promise<boolean> {
  const { error } = await supabase
    .from('blocked_times')
    .delete()
    .eq('id', id)

  if (error) {
    console.error('Error deleting blocked time:', error)
    return false
  }

  return true
}
