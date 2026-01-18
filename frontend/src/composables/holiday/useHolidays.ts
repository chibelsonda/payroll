import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import apiAxios from '@/lib/axios'
import type { Holiday, StoreHolidayData, UpdateHolidayData } from '@/types/holiday'
import { useNotification } from '@/composables/common/useNotification'

export function useHolidays() {
  const queryClient = useQueryClient()
  const notification = useNotification()

  const { data: holidays, isLoading, error, refetch } = useQuery({
    queryKey: ['holidays'],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: Holiday[]; meta?: any }>('/holidays')
      return response.data.data
    },
  })

  const createMutation = useMutation({
    mutationFn: async (data: StoreHolidayData) => {
      const response = await apiAxios.post<{ data: Holiday }>('/holidays', data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] })
      notification.showSuccess('Holiday created successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to create holiday')
    },
  })

  const updateMutation = useMutation({
    mutationFn: async ({ uuid, data }: { uuid: string; data: UpdateHolidayData }) => {
      const response = await apiAxios.put<{ data: Holiday }>(`/holidays/${uuid}`, data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] })
      notification.showSuccess('Holiday updated successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to update holiday')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: async (uuid: string) => {
      await apiAxios.delete(`/holidays/${uuid}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] })
      notification.showSuccess('Holiday deleted successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to delete holiday')
    },
  })

  return {
    holidays,
    isLoading,
    error,
    refetch,
    createHoliday: createMutation.mutateAsync,
    updateHoliday: updateMutation.mutateAsync,
    deleteHoliday: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending.value,
    isUpdating: updateMutation.isPending.value,
    isDeleting: deleteMutation.isPending.value,
  }
}
