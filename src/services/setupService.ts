import { api } from '@/lib/api'
import type { WaiverDocument } from '@/services/waiverService'

export interface SetupStatus {
  business_timezone: string
  timezones: string[]
  waiver: WaiverDocument | null
  lesson_count: number
  horse_count: number
  instructor_hours: boolean
  horse_hours: boolean
}
export async function fetchSetupStatus(): Promise<SetupStatus> {
  const { data, error } = await api<SetupStatus>('GET', '/admin/setup')
  if (!data || error) throw new Error(error || 'Unable to load setup.')
  return data
}
