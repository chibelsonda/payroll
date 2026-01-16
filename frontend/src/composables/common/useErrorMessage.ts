import { AxiosError } from 'axios'

/**
 * Extract a user-friendly error message from various error shapes.
 * Falls back to the provided default message.
 */
export const extractErrorMessage = (
  error: unknown,
  fallback = 'Something went wrong. Please try again.'
): string => {
  if (!error) return fallback

  const isObject = typeof error === 'object' && error !== null
  let message: string | undefined

  if (isObject && 'response' in error) {
    const resp = (error as AxiosError)?.response
    const dataMsg = (resp?.data as { message?: string } | undefined)?.message
    message = dataMsg ?? message
  }

  if (!message && isObject && 'message' in error) {
    message = (error as { message?: string }).message
  }

  return message || fallback
}
