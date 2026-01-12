import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Deduction } from '@/types/deduction'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchDeductions = async (page = 1): Promise<{ data: Deduction[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/deductions?page=${page}`)
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

const fetchDeduction = async (uuid: string): Promise<Deduction> => {
  const response = await axios.get(`/deductions/${uuid}`)
  return response.data.data
}

const createDeduction = async (data: {
  name: string
  type: 'fixed' | 'percentage'
}): Promise<Deduction> => {
  const response = await axios.post('/deductions', data)
  return response.data.data
}

const updateDeduction = async (uuid: string, data: {
  name?: string
  type?: 'fixed' | 'percentage'
}): Promise<Deduction> => {
  const response = await axios.put(`/deductions/${uuid}`, data)
  return response.data.data
}

const deleteDeduction = async (uuid: string): Promise<void> => {
  await axios.delete(`/deductions/${uuid}`)
}

// Composables
export const useDeductions = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['deductions', page],
    queryFn: () => fetchDeductions(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useDeduction = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['deduction', uuidValue],
    queryFn: () => fetchDeduction(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateDeduction = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createDeduction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['deductions'] })
    },
  })
}

export const useUpdateDeduction = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof updateDeduction>[1] }) =>
      updateDeduction(uuid, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['deductions'] })
      queryClient.invalidateQueries({ queryKey: ['deduction', variables.uuid] })
    },
  })
}

export const useDeleteDeduction = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteDeduction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['deductions'] })
    },
  })
}
