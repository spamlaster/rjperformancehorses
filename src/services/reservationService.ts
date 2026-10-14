import { api } from '@/lib/api'

export interface WeeklyReservation {
  id: string
  customer_id: string
  lesson_type_id: string
  lesson_name: string
  day_of_week: number
  start_time: string
  duration_minutes: number
  starts_on: string | null
  status: string
  business_timezone: string
  rider_names: string[]
  horse_ids: string[]
  skipped_dates: string[]
  upcoming_sessions: Array<{ date: string; skipped: boolean }>
}

export async function manageReservation(token: string, action: 'manage' | 'cancel' | 'skip' = 'manage', date?: string): Promise<WeeklyReservation> {
  const { data, error } = await api<WeeklyReservation>('POST', `/reservations/${action}`, { token, date })
  if (error || !data) throw new Error(error || 'Unable to load your reservation.')
  return data
}

export async function fetchWeeklyReservations(): Promise<WeeklyReservation[]> {
  const { data, error } = await api<WeeklyReservation[]>('GET', '/admin/recurring-lessons')
  if (error || !data) throw new Error(error || 'Unable to load weekly reservations.')
  return data
}

export async function cancelAdminReservation(id: string): Promise<void> {
  const { error } = await api('POST', `/admin/recurring-lessons/${encodeURIComponent(id)}/cancel`)
  if (error) throw new Error(error)
}

export async function cancelAdminBooking(id: string): Promise<void> {
  const { error } = await api('POST', `/admin/bookings/${encodeURIComponent(id)}/cancel`)
  if (error) throw new Error(error)
}

export const weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
