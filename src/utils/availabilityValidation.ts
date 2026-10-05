/**
 * Validate time windows for availability scheduling
 */

export interface TimeWindow {
  startTime: string  // HH:MM
  endTime: string    // HH:MM
  active?: boolean
}

/**
 * Check if start time is before end time
 */
export function isValidTimeWindow(window: TimeWindow): boolean {
  if (!window.startTime || !window.endTime) {
    return false
  }

  // Compare HH:MM strings lexicographically (works because format is fixed)
  return window.startTime < window.endTime
}

/**
 * Check if two time windows overlap
 * Adjacent windows (12:00-3:00 and 3:00-6:00) are allowed
 */
export function windowsOverlap(window1: TimeWindow, window2: TimeWindow): boolean {
  // Only check active windows
  if (window1.active === false || window2.active === false) {
    return false
  }

  // Check if one starts before the other ends
  // window1.end > window2.start AND window2.end > window1.start
  return window1.endTime > window2.startTime && window2.endTime > window1.startTime
}

/**
 * Validate a new window against existing windows for the same day/resource
 */
export function validateNewWindow(
  newWindow: TimeWindow,
  existingWindows: TimeWindow[]
): { valid: boolean; error?: string } {
  // Check basic validity
  if (!isValidTimeWindow(newWindow)) {
    return {
      valid: false,
      error: 'Start time must be before end time'
    }
  }

  // Check for overlaps with active windows
  for (const existing of existingWindows) {
    if (windowsOverlap(newWindow, existing)) {
      return {
        valid: false,
        error: `Time window overlaps with existing ${existing.startTime}–${existing.endTime}`
      }
    }
  }

  return { valid: true }
}

/**
 * Format time for display (HH:MM -> h:MM AM/PM)
 */
export function formatTime(timeStr: string): string {
  if (!timeStr || timeStr.length !== 5) {
    return timeStr
  }

  const [hours, minutes] = timeStr.split(':').map(Number)
  const ampm = hours >= 12 ? 'PM' : 'AM'
  const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours

  return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`
}
