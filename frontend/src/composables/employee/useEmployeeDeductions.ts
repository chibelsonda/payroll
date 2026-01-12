import { type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { EmployeeDeduction } from '@/types/deduction'

// API functions
const fetchEmployeeDeductions = async (employeeUuid: string): Promise<EmployeeDeduction[]> => {
  const response = await axios.get(`/employees/${employeeUuid}/deductions`)
  return response.data.data || []
}

const assignEmployeeDeduction = async (data: {
  employee_uuid: string
  deduction_uuid: string
  amount: number
}): Promise<EmployeeDeduction> => {
  const response = await axios.post(`/employees/${data.employee_uuid}/deductions`, data)
  return response.data.data
}

const removeEmployeeDeduction = async (employeeUuid: string, deductionUuid: string): Promise<void> => {
  await axios.delete(`/employees/${employeeUuid}/deductions/${deductionUuid}`)
}

// Composables
export const useEmployeeDeductions = (employeeUuid: string | Ref<string | null>) => {
  const employeeUuidValue = typeof employeeUuid === 'string' ? employeeUuid : employeeUuid.value
  return useQuery({
    queryKey: ['employee-deductions', employeeUuidValue],
    queryFn: () => fetchEmployeeDeductions(employeeUuidValue!),
    enabled: !!employeeUuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useAssignDeduction = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: assignEmployeeDeduction,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['employee-deductions', variables.employee_uuid] })
    },
  })
}

export const useRemoveDeduction = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ employeeUuid, deductionUuid }: { employeeUuid: string; deductionUuid: string }) =>
      removeEmployeeDeduction(employeeUuid, deductionUuid),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['employee-deductions', variables.employeeUuid] })
    },
  })
}
