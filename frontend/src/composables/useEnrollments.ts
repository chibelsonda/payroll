import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Enrollment } from '@/types/enrollment'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchEnrollments = async (page = 1): Promise<{ data: Enrollment[]; meta: PaginationMeta }> => {
  try {
    const response = await axios.get(`/enrollments?page=${page}`)
    return {
      data: response.data.data,
      meta: response.data.meta,
    }
  } catch (error: unknown) {
    // Handle 401 errors gracefully (user not authenticated)
    const axiosError = error as { response?: { status?: number } }
    if (axiosError?.response?.status === 401) {
      // Return empty data instead of throwing - query is disabled when not authenticated anyway
      return {
        data: [],
        meta: {
          current_page: 1,
          from: null,
          last_page: 1,
          per_page: 10,
          to: null,
          total: 0,
        },
      }
    }
    throw error
  }
}

const fetchEnrollment = async (uuid: string): Promise<Enrollment> => {
  const response = await axios.get(`/enrollments/${uuid}`)
  return response.data.data
}

const createEnrollment = async (data: { subject_id: number }): Promise<Enrollment> => {
  const response = await axios.post('/enrollments', data)
  return response.data.data
}

const updateEnrollment = async ({
  uuid,
  data
}: {
  uuid: string
  data: { status?: 'active' | 'inactive' }
}): Promise<Enrollment> => {
  const response = await axios.put(`/enrollments/${uuid}`, data)
  return response.data.data
}

const deleteEnrollment = async (uuid: string): Promise<void> => {
  await axios.delete(`/enrollments/${uuid}`)
}

// Composables
export const useEnrollments = (page = 1, keepPreviousData = true, enabled: boolean | Ref<boolean> | (() => boolean) = true) => {
  // Convert function to computed ref for reactivity
  const enabledValue = typeof enabled === 'function'
    ? computed(enabled)
    : enabled

  return useQuery({
    queryKey: ['enrollments', page],
    queryFn: () => fetchEnrollments(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    enabled: enabledValue,
    retry: false,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
    // Don't throw on error - handle gracefully
    throwOnError: false,
  })
}

export const useEnrollment = (uuid: string) => {
  return useQuery({
    queryKey: ['enrollment', uuid],
    queryFn: () => fetchEnrollment(uuid),
    enabled: !!uuid,
  })
}

export const useCreateEnrollment = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createEnrollment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['enrollments'] })
    },
  })
}

export const useUpdateEnrollment = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateEnrollment,
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['enrollments'] })
      queryClient.invalidateQueries({ queryKey: ['enrollment', variables.uuid] })
    },
  })
}

export const useDeleteEnrollment = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: deleteEnrollment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['enrollments'] })
    },
  })
}
