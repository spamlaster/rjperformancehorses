import { api } from '@/lib/api'
import type { WeeklyReservation } from '@/services/reservationService'

export interface SavedRider {
  id: string
  first_name: string
  last_name: string
  date_of_birth: string | null
}
export interface RiderAccount {
  customer: { id: string; first_name: string; last_name: string; email: string; phone: string | null }
  riders: SavedRider[]
  reservations: WeeklyReservation[]
}
export async function loadRiderAccount(): Promise<RiderAccount | null> {
  const result = await api<RiderAccount>('GET', '/rider/account')
  if (result.status === 401 || result.status === 403) return null
  if (result.error || !result.data) throw new Error(result.error || 'Unable to load your rider account.')
  return result.data
}
