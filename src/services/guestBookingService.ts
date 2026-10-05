/**
 * Guest Booking Service
 *
 * Handles creating real bookings through the controlled server-side RPC function.
 * This is the ONLY way guests can create bookings - direct database access is blocked by RLS.
 */

import { supabase } from '@/lib/supabase'

export interface GuestBookingPayload {
  lessonTypeId: string
  startTime: string // ISO 8601 datetime
  endTime: string   // ISO 8601 datetime
  customer: {
    firstName: string
    lastName: string
    email: string
    phone: string
  }
  riders: Array<{
    firstName: string
    lastName: string
    dateOfBirth?: string
  }>
}

export interface GuestBookingResult {
  success: boolean
  bookingId?: string
  customerId?: string
  error?: string
  message?: string
}

/**
 * Create a guest booking through the controlled RPC function
 *
 * This function:
 * 1. Rechecks availability (preventing double-booking race conditions)
 * 2. Finds or creates customer (with email-based deduplication)
 * 3. Creates or matches riders
 * 4. Creates booking atomically with all safety checks
 * 5. Assigns required horses
 *
 * @param payload Guest booking data
 * @returns Result with booking ID or error message
 */
export async function createGuestBooking(payload: GuestBookingPayload): Promise<GuestBookingResult> {
  try {
    // Build riders JSONB array
    const ridersJsonb = payload.riders.map(r => ({
      firstName: r.firstName,
      lastName: r.lastName,
      dateOfBirth: r.dateOfBirth || null,
    }))

    const { data, error } = await supabase.rpc('create_guest_booking', {
      p_lesson_type_id: payload.lessonTypeId,
      p_start_time: payload.startTime,
      p_end_time: payload.endTime,
      p_customer_first_name: payload.customer.firstName,
      p_customer_last_name: payload.customer.lastName,
      p_customer_email: payload.customer.email,
      p_customer_phone: payload.customer.phone,
      p_riders: ridersJsonb,
    })

    if (error) {
      console.error('Guest booking RPC error:', error)
      return {
        success: false,
        error: error.message || 'Failed to create booking',
      }
    }

    if (data?.error) {
      console.error('Guest booking validation error:', data.error)
      return {
        success: false,
        error: data.error,
      }
    }

    return {
      success: data?.success || false,
      bookingId: data?.booking_id,
      customerId: data?.customer_id,
      message: data?.message,
    }
  } catch (err) {
    console.error('Guest booking exception:', err)
    return {
      success: false,
      error: 'An unexpected error occurred',
    }
  }
}
