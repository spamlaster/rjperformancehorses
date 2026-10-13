/**
 * Guest Booking Service
 *
 * Handles creating real bookings through the Laravel API (POST /api/bookings/guest).
 * This is the ONLY way guests can create bookings; all other writes require an admin session.
 */

import { api } from '@/lib/api'

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
 * Create a guest booking through the API
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
    const { data, error } = await api<{ success: boolean; booking_id: string; customer_id: string; message: string }>('POST', '/bookings/guest', {
      lesson_type_id: payload.lessonTypeId,
      start_time: payload.startTime,
      end_time: payload.endTime,
      customer: {
        first_name: payload.customer.firstName,
        last_name: payload.customer.lastName,
        email: payload.customer.email,
        phone: payload.customer.phone,
      },
      riders: payload.riders.map(r => ({
        first_name: r.firstName,
        last_name: r.lastName,
        date_of_birth: r.dateOfBirth || null,
      })),
    })

    if (error || !data) {
      console.error('Guest booking error:', error)
      return {
        success: false,
        error: error || 'Failed to create booking',
      }
    }

    return {
      success: data.success,
      bookingId: data.booking_id,
      customerId: data.customer_id,
      message: data.message,
    }
  } catch (err) {
    console.error('Guest booking exception:', err)
    return {
      success: false,
      error: 'An unexpected error occurred',
    }
  }
}
