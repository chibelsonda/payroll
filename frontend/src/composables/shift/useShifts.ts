import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import apiAxios from '@/lib/axios'
import type { Shift, StoreShiftData, UpdateShiftData } from '@/types/shift'
import { useNotification } from '@/composables/common/useNotification'

export function useShifts() {
  const queryClient = useQueryClient()
  const notification = useNotification()

  const { data: shifts, isLoading, error, refetch } = useQuery({
    queryKey: ['shifts'],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: Shift[]; meta?: any }>('/shifts')
      return response.data.data
    },
  })

  const createMutation = useMutation({
    mutationFn: async (data: StoreShiftData) => {
      const response = await apiAxios.post<{ data: Shift }>('/shifts', data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shifts'] })
      notification.showSuccess('Shift created successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to create shift')
    },
  })

  const updateMutation = useMutation({
    mutationFn: async ({ uuid, data }: { uuid: string; data: UpdateShiftData }) => {
      const response = await apiAxios.put<{ data: Shift }>(`/shifts/${uuid}`, data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shifts'] })
      notification.showSuccess('Shift updated successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to update shift')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: async (uuid: string) => {
      await apiAxios.delete(`/shifts/${uuid}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shifts'] })
      notification.showSuccess('Shift deleted successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to delete shift')
    },
  })

  return {
    shifts,
    isLoading,
    error,
    refetch,
    createShift: createMutation.mutateAsync,
    updateShift: updateMutation.mutateAsync,
    deleteShift: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending.value,
    isUpdating: updateMutation.isPending.value,
    isDeleting: deleteMutation.isPending.value,
  }
}
