/**
 * Public Booking Availability Service
 *
 * Calculates which lesson times are bookable based on:
 * - Instructor recurring availability
 * - Horse recurring availability (per-horse)
 * - Blocked times (instructor and horse-specific)
 * - Existing bookings (pending/confirmed only)
 * - Lesson type eligibility
 *
 * This service calls server-side RPC functions that enforce all scheduling rules.
 * The availability calculation is authoritative on the server.
 */

import { supabase } from '@/lib/supabase'

export interface BookableSlot {
  startTime: string  // HH:MM
  endTime: string    // HH:MM
  available: boolean
}

/**
 * Get available booking slots for a lesson type on a specific date
 *
 * @param lessonTypeId - UUID of the lesson type
 * @param date - Date in YYYY-MM-DD format
 * @returns Array of available time slots
 *
 * Business Rules Enforced:
 * 1. Instructor must have active recurring availability covering the lesson
 * 2. Lesson duration must fit completely within an availability window
 * 3. The required number of distinct eligible horses must have active recurring availability
 * 4. Horses with ZERO recurring availability rows are NOT available
 * 5. No instructor blocks overlapping the slot
 * 6. No horse-specific blocks overlapping the slot
 * 7. No existing pending/confirmed bookings conflicting
 * 8. Cancelled/completed bookings do NOT block availability
 */
export async function getBookableSlots(
  lessonTypeId: string,
  date: string
): Promise<BookableSlot[]> {
  // Validate inputs
  if (!lessonTypeId || !date) {
    console.error('Missing required parameters for availability lookup')
    return []
  }

  const { data, error } = await supabase.rpc('get_bookable_slots', {
    p_lesson_type_id: lessonTypeId,
    p_date: date,
  })
  if (error) throw error
  return (data || []).map((slot: { start_time: string; end_time: string; available: boolean }) => ({
    startTime: slot.start_time,
    endTime: slot.end_time,
    available: slot.available,
  }))
}

/**
 * Format time string for display
 * Converts HH:MM to h:MM AM/PM
 */
export function formatTimeForDisplay(timeStr: string): string {
  if (!timeStr || timeStr.length !== 5) {
    return timeStr
  }

  const [hours, minutes] = timeStr.split(':').map(Number)
  const ampm = hours >= 12 ? 'PM' : 'AM'
  const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours

  return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`
}
