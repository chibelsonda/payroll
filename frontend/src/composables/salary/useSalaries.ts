import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Salary } from '@/types/salary'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchSalaries = async (page = 1): Promise<{ data: Salary[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/salaries?page=${page}`)
  const paginationMeta = response.data.meta?.pagination || {}
  return {
    data: response.data.data || [],
    meta: {
      current_page: paginationMeta.current_page || page,
      from: paginationMeta.from ?? null,
      last_page: paginationMeta.last_page || 1,
      per_page: paginationMeta.per_page || 10,
      to: paginationMeta.to ?? null,
      total: paginationMeta.total || 0,
    },
  }
}

const fetchSalary = async (uuid: string): Promise<Salary> => {
  const response = await axios.get(`/salaries/${uuid}`)
  return response.data.data
}

const fetchEmployeeSalaries = async (employeeUuid: string): Promise<Salary[]> => {
  const response = await axios.get(`/employees/${employeeUuid}/salaries`)
  return response.data.data || []
}

const createSalary = async (data: {
  employee_uuid: string
  amount: number
  effective_from: string
}): Promise<Salary> => {
  const response = await axios.post('/salaries', data)
  return response.data.data
}

const updateSalary = async (uuid: string, data: {
  amount?: number
  effective_from?: string
}): Promise<Salary> => {
  const response = await axios.put(`/salaries/${uuid}`, data)
  return response.data.data
}

const deleteSalary = async (uuid: string): Promise<void> => {
  await axios.delete(`/salaries/${uuid}`)
}

// Composables
export const useSalaries = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['salaries', page],
    queryFn: () => fetchSalaries(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useSalary = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['salary', uuidValue],
    queryFn: () => fetchSalary(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useEmployeeSalaries = (employeeUuid: string | Ref<string | null>) => {
  const employeeUuidValue = typeof employeeUuid === 'string' ? employeeUuid : employeeUuid.value
  return useQuery({
    queryKey: ['employee-salaries', employeeUuidValue],
    queryFn: () => fetchEmployeeSalaries(employeeUuidValue!),
    enabled: !!employeeUuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateSalary = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createSalary,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['salaries'] })
      queryClient.invalidateQueries({ queryKey: ['employee-salaries', variables.employee_uuid] })
    },
  })
}

export const useUpdateSalary = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof updateSalary>[1] }) =>
      updateSalary(uuid, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['salaries'] })
      queryClient.invalidateQueries({ queryKey: ['salary', variables.uuid] })
    },
  })
}

export const useDeleteSalary = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteSalary,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['salaries'] })
    },
  })
}
