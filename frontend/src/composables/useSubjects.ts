import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from 'axios'

export interface Subject {
  id: number
  uuid: string
  code: string
  name: string
  description?: string
  credits: number
  created_at: string
  updated_at: string
}

// API functions
const fetchSubjects = async (page = 1): Promise<{ data: Subject[]; meta: any }> => {
  const response = await axios.get(`/api/v1/subjects?page=${page}`)
  return {
    data: response.data.data,
    meta: response.data.meta,
  }
}

const fetchSubject = async (uuid: string): Promise<Subject> => {
  const response = await axios.get(`/api/v1/subjects/${uuid}`)
  return response.data.data
}

const createSubject = async (data: {
  code: string
  name: string
  description?: string
  credits: number
}): Promise<Subject> => {
  const response = await axios.post('/api/v1/subjects', data)
  return response.data.data
}

const updateSubject = async ({
  uuid,
  data
}: {
  uuid: string
  data: { code?: string; name?: string; description?: string; credits?: number }
}): Promise<Subject> => {
  const response = await axios.put(`/api/v1/subjects/${uuid}`, data)
  return response.data.data
}

const deleteSubject = async (uuid: string): Promise<void> => {
  await axios.delete(`/api/v1/subjects/${uuid}`)
}

// Composables
export const useSubjects = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['subjects', page],
    queryFn: () => fetchSubjects(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
  })
}

export const useSubject = (uuid: string) => {
  return useQuery({
    queryKey: ['subject', uuid],
    queryFn: () => fetchSubject(uuid),
    enabled: !!uuid,
  })
}

export const useCreateSubject = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createSubject,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['subjects'] })
    },
  })
}

export const useUpdateSubject = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateSubject,
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['subjects'] })
      queryClient.invalidateQueries({ queryKey: ['subject', variables.uuid] })
    },
  })
}

export const useDeleteSubject = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: deleteSubject,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['subjects'] })
    },
  })
}