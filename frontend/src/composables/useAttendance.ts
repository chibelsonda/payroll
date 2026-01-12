import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Attendance } from '@/types/attendance'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchAttendances = async (page = 1): Promise<{ data: Attendance[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/attendances?page=${page}`)
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

const fetchAttendance = async (uuid: string): Promise<Attendance> => {
  const response = await axios.get(`/attendances/${uuid}`)
  return response.data.data
}

const createAttendance = async (data: {
  employee_uuid: string
  date: string
  time_in?: string
  time_out?: string
  hours_worked?: number
}): Promise<Attendance> => {
  const response = await axios.post('/attendances', data)
  return response.data.data
}

const updateAttendance = async (uuid: string, data: {
  employee_uuid: string
  date: string
  time_in?: string
  time_out?: string
  hours_worked?: number
}): Promise<Attendance> => {
  const response = await axios.put(`/attendances/${uuid}`, data)
  return response.data.data
}

const deleteAttendance = async (uuid: string): Promise<void> => {
  await axios.delete(`/attendances/${uuid}`)
}

// Composables
export const useAttendances = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['attendances', page],
    queryFn: () => fetchAttendances(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useAttendance = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['attendance', uuidValue],
    queryFn: () => fetchAttendance(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
    },
  })
}

export const useUpdateAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof updateAttendance>[1] }) =>
      updateAttendance(uuid, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance', variables.uuid] })
    },
  })
}

export const useDeleteAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
    },
  })
}
