import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Employee } from '@/types/employee'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchEmployees = async (page = 1): Promise<{ data: Employee[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/employees?page=${page}`)
  // Handle the API response structure: { success, message, data, meta: { pagination: {...} } }
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

const fetchEmployee = async (uuid: string): Promise<Employee> => {
  const response = await axios.get(`/employees/${uuid}`)
  return response.data.data
}

const createEmployee = async (data: {
  first_name: string
  last_name: string
  email: string
  password: string
  employee_no: string
  company_uuid?: string | null
  department_uuid?: string | null
  position_uuid?: string | null
  employment_type?: 'regular' | 'contractual' | 'probationary' | null
  hire_date?: string | null
  termination_date?: string | null
  status?: 'active' | 'inactive' | 'terminated' | null
}): Promise<Employee> => {
  const response = await axios.post('/employees', data)
  return response.data.data
}

const updateEmployee = async ({
  uuid,
  data
}: {
  uuid: string
  data: {
    first_name?: string
    last_name?: string
    email?: string
    employee_no?: string
    company_uuid?: string | null
    department_uuid?: string | null
    position_uuid?: string | null
    employment_type?: 'regular' | 'contractual' | 'probationary' | null
    hire_date?: string | null
    termination_date?: string | null
    status?: 'active' | 'inactive' | 'terminated' | null
  }
}): Promise<Employee> => {
  const response = await axios.put(`/employees/${uuid}`, data)
  return response.data.data
}

const deleteEmployee = async (uuid: string): Promise<void> => {
  await axios.delete(`/employees/${uuid}`)
}

const importEmployees = async (file: File): Promise<{ created: number; failed: number; errors: Array<{ row: number; message: string }> }> => {
  const formData = new FormData()
  formData.append('file', file)
  const response = await axios.post('/employees/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data.data
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
    // refetchOnMount is true by default (set in main.ts)
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

export const useImportEmployees = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: importEmployees,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] })
    },
  })
}
