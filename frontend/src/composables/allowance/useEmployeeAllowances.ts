import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import apiAxios from '@/lib/axios'
import type { EmployeeAllowance, StoreEmployeeAllowanceData, UpdateEmployeeAllowanceData } from '@/types/allowance'
import { useNotification } from '@/composables/common/useNotification'

export function useEmployeeAllowances(employeeUuid?: string) {
  const queryClient = useQueryClient()
  const notification = useNotification()

  const queryKey = employeeUuid
    ? ['employee-allowances', employeeUuid]
    : ['employee-allowances']

  const { data: employeeAllowances, isLoading, error, refetch } = useQuery({
    queryKey,
    queryFn: async () => {
      const params = employeeUuid ? { employee_uuid: employeeUuid } : {}
      const response = await apiAxios.get<{ data: EmployeeAllowance[] }>('/employee-allowances', { params })
      return response.data.data
    },
  })

  const createMutation = useMutation({
    mutationFn: async (data: StoreEmployeeAllowanceData) => {
      const response = await apiAxios.post<{ data: EmployeeAllowance }>('/employee-allowances', data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employee-allowances'] })
      notification.showSuccess('Employee allowance created successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to create employee allowance')
    },
  })

  const updateMutation = useMutation({
    mutationFn: async ({ uuid, data }: { uuid: string; data: UpdateEmployeeAllowanceData }) => {
      const response = await apiAxios.put<{ data: EmployeeAllowance }>(`/employee-allowances/${uuid}`, data)
      return response.data.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employee-allowances'] })
      notification.showSuccess('Employee allowance updated successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to update employee allowance')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: async (uuid: string) => {
      await apiAxios.delete(`/employee-allowances/${uuid}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employee-allowances'] })
      notification.showSuccess('Employee allowance deleted successfully')
    },
    onError: (error: unknown) => {
      const apiError = error as { response?: { data?: { message?: string } } }
      notification.showError(apiError?.response?.data?.message || 'Failed to delete employee allowance')
    },
  })

  return {
    employeeAllowances,
    isLoading,
    error,
    refetch,
    createEmployeeAllowance: createMutation.mutateAsync,
    updateEmployeeAllowance: updateMutation.mutateAsync,
    deleteEmployeeAllowance: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending.value,
    isUpdating: updateMutation.isPending.value,
    isDeleting: deleteMutation.isPending.value,
  }
}
