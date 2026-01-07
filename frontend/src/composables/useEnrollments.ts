import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Enrollment } from '@/types/enrollment'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchEnrollments = async (page = 1): Promise<{ data: Enrollment[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/enrollments?page=${page}`)
  return {
    data: response.data.data,
    meta: response.data.meta,
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
export const useEnrollments = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['enrollments', page],
    queryFn: () => fetchEnrollments(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
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
