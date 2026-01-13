import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import apiAxios, { webAxios } from '@/lib/axios'
import type { Invitation, CreateInvitationData, AcceptInvitationData } from '@/types/invitation'
import { useNotification } from '@/composables'

/**
 * Fetch invitations for the active company
 */
export function fetchInvitations() {
  return apiAxios
    .get<{ data: Invitation[] }>('/invitations')
    .then((res) => res.data.data)
}

/**
 * Composable to fetch invitations
 */
export function useInvitations() {
  return useQuery({
    queryKey: ['invitations'],
    queryFn: fetchInvitations,
    staleTime: 2 * 60 * 1000, // 2 minutes
  })
}

/**
 * Composable to create an invitation
 */
export function useCreateInvitation() {
  const queryClient = useQueryClient()
  const notification = useNotification()

  return useMutation({
    mutationFn: async (data: CreateInvitationData) => {
      // Get CSRF cookie first (required for Sanctum)
      await webAxios.get('/sanctum/csrf-cookie')
      // Then make the POST request
      return apiAxios
        .post<{ data: Invitation }>('/invitations', data)
        .then((res) => res.data.data)
    },
    onSuccess: () => {
      // Invalidate invitations query to refetch
      queryClient.invalidateQueries({ queryKey: ['invitations'] })
      notification.showSuccess('Invitation sent successfully!')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      const message =
        apiError?.response?.data?.message || 'Failed to send invitation. Please try again.'
      notification.showError(message)
    },
  })
}

/**
 * Composable to accept an invitation
 */
export function useAcceptInvitation() {
  const queryClient = useQueryClient()
  const notification = useNotification()

  return useMutation({
    mutationFn: async (data: AcceptInvitationData) => {
      // Get CSRF cookie first (required for Sanctum)
      await webAxios.get('/sanctum/csrf-cookie')
      // Then make the POST request
      return apiAxios
        .post<{ data: Invitation }>('/invitations/accept', data)
        .then((res) => res.data.data)
    },
    onSuccess: () => {
      // Invalidate user and companies queries to get updated data
      queryClient.invalidateQueries({ queryKey: ['user'] })
      queryClient.invalidateQueries({ queryKey: ['companies'] })
      notification.showSuccess('Invitation accepted successfully!')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      const message =
        apiError?.response?.data?.message || 'Failed to accept invitation. Please try again.'
      notification.showError(message)
    },
  })
}

/**
 * Composable to cancel an invitation
 */
export function useCancelInvitation() {
  const queryClient = useQueryClient()
  const notification = useNotification()

  return useMutation({
    mutationFn: async (invitationUuid: string) => {
      // Get CSRF cookie first (required for Sanctum)
      await webAxios.get('/sanctum/csrf-cookie')
      // Then make the DELETE request
      return apiAxios.delete(`/invitations/${invitationUuid}`).then((res) => res.data)
    },
    onSuccess: () => {
      // Invalidate invitations query to refetch
      queryClient.invalidateQueries({ queryKey: ['invitations'] })
      notification.showSuccess('Invitation cancelled successfully!')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      const message =
        apiError?.response?.data?.message || 'Failed to cancel invitation. Please try again.'
      notification.showError(message)
    },
  })
}
