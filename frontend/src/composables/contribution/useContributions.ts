import type { Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Contribution, EmployeeContribution } from '@/types/contribution'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchContributions = async (page = 1): Promise<{ data: Contribution[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/contributions?page=${page}`)
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

const fetchContribution = async (uuid: string): Promise<Contribution> => {
  const response = await axios.get(`/contributions/${uuid}`)
  return response.data.data
}

const fetchEmployeeContributions = async (employeeUuid: string): Promise<EmployeeContribution[]> => {
  const response = await axios.get(`/employees/${employeeUuid}/contributions`)
  return response.data.data || []
}

const createContribution = async (data: {
  name: string
  employee_share: number
  employer_share: number
}): Promise<Contribution> => {
  const response = await axios.post('/contributions', data)
  return response.data.data
}

const updateContribution = async (uuid: string, data: {
  name?: string
  employee_share?: number
  employer_share?: number
}): Promise<Contribution> => {
  const response = await axios.put(`/contributions/${uuid}`, data)
  return response.data.data
}

const deleteContribution = async (uuid: string): Promise<void> => {
  await axios.delete(`/contributions/${uuid}`)
}

const assignEmployeeContribution = async (data: {
  employee_uuid: string
  contribution_uuid: string
}): Promise<EmployeeContribution> => {
  const response = await axios.post(`/employees/${data.employee_uuid}/contributions`, data)
  return response.data.data
}

const removeEmployeeContribution = async (employeeUuid: string, contributionUuid: string): Promise<void> => {
  await axios.delete(`/employees/${employeeUuid}/contributions/${contributionUuid}`)
}

// Composables
export const useContributions = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['contributions', page],
    queryFn: () => fetchContributions(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useContribution = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['contribution', uuidValue],
    queryFn: () => fetchContribution(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useEmployeeContributions = (employeeUuid: string | Ref<string | null>) => {
  const employeeUuidValue = typeof employeeUuid === 'string' ? employeeUuid : employeeUuid.value
  return useQuery({
    queryKey: ['employee-contributions', employeeUuidValue],
    queryFn: () => fetchEmployeeContributions(employeeUuidValue!),
    enabled: !!employeeUuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateContribution = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createContribution,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contributions'] })
    },
  })
}

export const useUpdateContribution = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof updateContribution>[1] }) =>
      updateContribution(uuid, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['contributions'] })
      queryClient.invalidateQueries({ queryKey: ['contribution', variables.uuid] })
    },
  })
}

export const useDeleteContribution = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteContribution,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contributions'] })
    },
  })
}

export const useAssignEmployeeContribution = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: assignEmployeeContribution,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['employee-contributions', variables.employee_uuid] })
    },
  })
}

export const useRemoveEmployeeContribution = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ employeeUuid, contributionUuid }: { employeeUuid: string; contributionUuid: string }) =>
      removeEmployeeContribution(employeeUuid, contributionUuid),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['employee-contributions', variables.employeeUuid] })
    },
  })
}
