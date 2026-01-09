import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Employee } from '@/types/auth'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchEmployees = async (page = 1): Promise<{ data: Employee[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/employees?page=${page}`)
  return {
    data: response.data.data,
    meta: response.data.meta,
  }
}

const fetchEmployee = async (uuid: string): Promise<Employee> => {
  const response = await axios.get(`/employees/${uuid}`)
  return response.data.data
}

const createEmployee = async (data: { user_id: number; employee_id: string }): Promise<Employee> => {
  const response = await axios.post('/employees', data)
  return response.data.data
}

const updateEmployee = async ({ uuid, data }: { uuid: string; data: { employee_id?: string } }): Promise<Employee> => {
  const response = await axios.put(`/employees/${uuid}`, data)
  return response.data.data
}

const deleteEmployee = async (uuid: string): Promise<void> => {
  await axios.delete(`/employees/${uuid}`)
}

// Composables
export const useEmployees = (page = 1, keepPreviousData = true, enabled: boolean | Ref<boolean> | (() => boolean) = true) => {
  // Convert function to computed ref for reactivity
  const enabledValue = typeof enabled === 'function'
    ? computed(enabled)
    : enabled

  return useQuery({
    queryKey: ['employees', page],
    queryFn: () => fetchEmployees(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    enabled: enabledValue,
    retry: false,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
  })
}

export const useEmployee = (uuid: string) => {
  return useQuery({
    queryKey: ['employee', uuid],
    queryFn: () => fetchEmployee(uuid),
    enabled: !!uuid,
  })
}

export const useCreateEmployee = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createEmployee,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] })
    },
  })
}

export const useUpdateEmployee = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateEmployee,
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['employees'] })
      queryClient.invalidateQueries({ queryKey: ['employee', variables.uuid] })
    },
  })
}

export const useDeleteEmployee = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: deleteEmployee,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] })
    },
  })
}
