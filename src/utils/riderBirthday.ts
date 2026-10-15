export function currentDateInTimezone(timeZone?: string): string {
  const parts = new Intl.DateTimeFormat('en-US', { timeZone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date())
  const part = (type: string) => parts.find(p => p.type === type)?.value || ''
  return `${part('year')}-${part('month')}-${part('day')}`
}

export function birthdayError(value: string | undefined, today = currentDateInTimezone()): string {
  if (!value) return 'Enter the rider’s date of birth.'
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return 'Enter a valid date of birth.'
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(Date.UTC(year, month - 1, day))
  if (year < 1900 || date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return 'Enter a valid date of birth, including the four-digit birth year (1900 or later).'
  if (value > today) return 'Date of birth cannot be in the future.'
  return ''
}

export function riderIsMinor(birthday: string | undefined, today: string): boolean {
  if (birthdayError(birthday, today)) return false
  const [year, month, day] = birthday!.split('-').map(Number)
  const [currentYear, currentMonth, currentDay] = today.split('-').map(Number)
  const age = currentYear - year - (currentMonth < month || (currentMonth === month && currentDay < day) ? 1 : 0)
  return age < 18
}
