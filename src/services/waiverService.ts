import { api } from '@/lib/api'

export interface WaiverDocument {
  id: string
  title: string
  version: number
  content: string
  effective_date: string
  document_hash: string
  current_date: string
}
export interface WaiverSignature {
  signer_name: string
  relationship_to_rider: '' | 'self' | 'parent' | 'legal_guardian'
  accepted: boolean
  authorized: boolean
}
export interface BookingWaiver {
  id: string
  document_hash: string
  signatures: WaiverSignature[]
}
export interface SignedWaiver {
  id: string
  rider_name: string
  signer_name: string
  signer_email: string
  relationship_to_rider: string
  accepted_at: string
  waiver_title: string | null
  waiver_version: number | null
  waiver_content: string | null
}
export async function fetchCurrentWaiver(): Promise<WaiverDocument> {
  const { data, error } = await api<WaiverDocument>('GET', '/waiver')
  if (error || !data) throw new Error(error || 'Unable to load the waiver. Please try again.')
  return data
}
export async function fetchSignedWaivers(id: string, recurring: boolean): Promise<SignedWaiver[]> {
  const path = recurring ? 'recurring-lessons' : 'bookings'
  const { data, error } = await api<SignedWaiver[]>('GET', `/admin/${path}/${encodeURIComponent(id)}/waivers`)
  if (error || !data) throw new Error(error || 'Unable to load signed waivers.')
  return data
}
