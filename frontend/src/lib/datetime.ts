/**
 * DateTime utility functions for handling datetime conversions
 * between frontend (datetime-local) and backend (ISO string) formats
 */

/**
 * Converts a datetime-local input value to backend format
 *
 * datetime-local inputs provide "YYYY-MM-DDTHH:mm" format (local time, no timezone)
 * Backend expects "YYYY-MM-DDTHH:mm:ss" format (treated as UTC, no timezone conversion)
 *
 * @param datetimeLocalValue - The value from a datetime-local input (e.g., "2024-01-15T08:00")
 * @returns Formatted datetime string for backend (e.g., "2024-01-15T08:00:00")
 *
 * @example
 * ```ts
 * const backendFormat = formatDateTimeForBackend("2024-01-15T08:00")
 * // Returns: "2024-01-15T08:00:00"
 * ```
 */
export function formatDateTimeForBackend(datetimeLocalValue: string): string {
  if (!datetimeLocalValue) {
    throw new Error('DateTime value is required')
  }

  // datetime-local format is "YYYY-MM-DDTHH:mm"
  // Append ":00" for seconds to make it "YYYY-MM-DDTHH:mm:00"
  // Backend will parse this as UTC without timezone conversion
  if (datetimeLocalValue.includes('T') && !datetimeLocalValue.includes(':')) {
    // Handle case where only date is provided
    return `${datetimeLocalValue}T00:00:00`
  }

  // If seconds are already included, use as-is
  if (datetimeLocalValue.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/)) {
    return datetimeLocalValue
  }

  // If format is "YYYY-MM-DDTHH:mm", append ":00"
  if (datetimeLocalValue.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/)) {
    return `${datetimeLocalValue}:00`
  }

  // If format is "YYYY-MM-DDTHH:mm:ss", use as-is
  if (datetimeLocalValue.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/)) {
    return datetimeLocalValue
  }

  throw new Error(`Invalid datetime-local format: ${datetimeLocalValue}`)
}

/**
 * Converts a backend datetime string to datetime-local format
 *
 * Backend provides ISO datetime strings (e.g., "2024-01-15T08:00:00.000000Z")
 * datetime-local inputs need "YYYY-MM-DDTHH:mm" format
 *
 * @param backendDateTime - The datetime string from backend (ISO format)
 * @returns Formatted datetime string for datetime-local input (e.g., "2024-01-15T08:00")
 *
 * @example
 * ```ts
 * const localFormat = formatDateTimeForInput("2024-01-15T08:00:00.000000Z")
 * // Returns: "2024-01-15T08:00"
 * ```
 */
export function formatDateTimeForInput(backendDateTime: string | Date): string {
  if (!backendDateTime) {
    return ''
  }

  const date = typeof backendDateTime === 'string' ? new Date(backendDateTime) : backendDateTime

  if (isNaN(date.getTime())) {
    throw new Error(`Invalid datetime: ${backendDateTime}`)
  }

  // Format as "YYYY-MM-DDTHH:mm" for datetime-local input
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}

/**
 * Formats a datetime for display purposes
 *
 * @param datetime - The datetime string or Date object
 * @param options - Intl.DateTimeFormatOptions for customization
 * @returns Formatted datetime string for display
 *
 * @example
 * ```ts
 * formatDateTimeForDisplay("2024-01-15T08:00:00Z")
 * // Returns: "Jan 15, 2024, 8:00 AM" (locale-dependent)
 *
 * formatDateTimeForDisplay("2024-01-15T08:00:00Z", {
 *   dateStyle: 'short',
 *   timeStyle: 'short'
 * })
 * ```
 */
export function formatDateTimeForDisplay(
  datetime: string | Date,
  options?: Intl.DateTimeFormatOptions
): string {
  if (!datetime) {
    return ''
  }

  const date = typeof datetime === 'string' ? new Date(datetime) : datetime

  if (isNaN(date.getTime())) {
    return ''
  }

  const defaultOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  }

  return new Intl.DateTimeFormat('en-US', defaultOptions).format(date)
}

/**
 * Formats only the time portion of a datetime for display
 *
 * @param datetime - The datetime string or Date object
 * @returns Formatted time string (e.g., "8:00 AM")
 *
 * @example
 * ```ts
 * formatTimeForDisplay("2024-01-15T08:00:00Z")
 * // Returns: "8:00 AM"
 * ```
 */
export function formatTimeForDisplay(datetime: string | Date): string {
  if (!datetime) {
    return ''
  }

  const date = typeof datetime === 'string' ? new Date(datetime) : datetime

  if (isNaN(date.getTime())) {
    return ''
  }

  return new Intl.DateTimeFormat('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).format(date)
}

/**
 * Formats only the date portion of a datetime for display
 *
 * @param datetime - The datetime string or Date object
 * @returns Formatted date string (e.g., "Jan 15, 2024")
 *
 * @example
 * ```ts
 * formatDateForDisplay("2024-01-15T08:00:00Z")
 * // Returns: "Jan 15, 2024"
 * ```
 */
export function formatDateForDisplay(datetime: string | Date): string {
  if (!datetime) {
    return ''
  }

  const date = typeof datetime === 'string' ? new Date(datetime) : datetime

  if (isNaN(date.getTime())) {
    return ''
  }

  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date)
}

/**
 * Validates if a string is a valid datetime-local format
 *
 * @param value - The string to validate
 * @returns true if valid, false otherwise
 */
export function isValidDateTimeLocal(value: string): boolean {
  if (!value) return false
  return /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)
}
