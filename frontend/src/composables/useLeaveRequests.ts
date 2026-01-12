import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { LeaveRequest } from '@/types/leave'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchLeaveRequests = async (page = 1): Promise<{ data: LeaveRequest[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/leave-requests?page=${page}`)
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

const fetchLeaveRequest = async (uuid: string): Promise<LeaveRequest> => {
  const response = await axios.get(`/leave-requests/${uuid}`)
  return response.data.data
}

const createLeaveRequest = async (data: {
  employee_uuid: string
  leave_type: 'vacation' | 'sick'
  start_date: string
  end_date: string
}): Promise<LeaveRequest> => {
  const response = await axios.post('/leave-requests', data)
  return response.data.data
}

const approveLeaveRequest = async (uuid: string): Promise<LeaveRequest> => {
  const response = await axios.post(`/leave-requests/${uuid}/approve`)
  return response.data.data
}

const rejectLeaveRequest = async (uuid: string): Promise<LeaveRequest> => {
  const response = await axios.post(`/leave-requests/${uuid}/reject`)
  return response.data.data
}

const deleteLeaveRequest = async (uuid: string): Promise<void> => {
  await axios.delete(`/leave-requests/${uuid}`)
}

// Composables
export const useLeaveRequests = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['leave-requests', page],
    queryFn: () => fetchLeaveRequests(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
  })
}

export const useLeaveRequest = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['leave-request', uuidValue],
    queryFn: () => fetchLeaveRequest(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
  })
}

export const useCreateLeaveRequest = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createLeaveRequest,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leave-requests'] })
    },
  })
}

export const useApproveLeaveRequest = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: approveLeaveRequest,
    onSuccess: (_, uuid) => {
      queryClient.invalidateQueries({ queryKey: ['leave-requests'] })
      queryClient.invalidateQueries({ queryKey: ['leave-request', uuid] })
    },
  })
}

export const useRejectLeaveRequest = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: rejectLeaveRequest,
    onSuccess: (_, uuid) => {
      queryClient.invalidateQueries({ queryKey: ['leave-requests'] })
      queryClient.invalidateQueries({ queryKey: ['leave-request', uuid] })
    },
  })
}

export const useDeleteLeaveRequest = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteLeaveRequest,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leave-requests'] })
    },
  })
}
