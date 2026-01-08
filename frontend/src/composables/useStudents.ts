import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Student } from '@/types/auth'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchStudents = async (page = 1): Promise<{ data: Student[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/students?page=${page}`)
  return {
    data: response.data.data,
    meta: response.data.meta,
  }
}

const fetchStudent = async (uuid: string): Promise<Student> => {
  const response = await axios.get(`/students/${uuid}`)
  return response.data.data
}

const createStudent = async (data: { user_id: number; student_id: string }): Promise<Student> => {
  const response = await axios.post('/students', data)
  return response.data.data
}

const updateStudent = async ({ uuid, data }: { uuid: string; data: { student_id?: string } }): Promise<Student> => {
  const response = await axios.put(`/students/${uuid}`, data)
  return response.data.data
}

const deleteStudent = async (uuid: string): Promise<void> => {
  await axios.delete(`/students/${uuid}`)
}

// Composables
export const useStudents = (page = 1, keepPreviousData = true, enabled: boolean | Ref<boolean> | (() => boolean) = true) => {
  // Convert function to computed ref for reactivity
  const enabledValue = typeof enabled === 'function'
    ? computed(enabled)
    : enabled

  return useQuery({
    queryKey: ['students', page],
    queryFn: () => fetchStudents(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    enabled: enabledValue,
    retry: false,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
  })
}

export const useStudent = (uuid: string) => {
  return useQuery({
    queryKey: ['student', uuid],
    queryFn: () => fetchStudent(uuid),
    enabled: !!uuid,
  })
}

export const useCreateStudent = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createStudent,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] })
    },
  })
}

export const useUpdateStudent = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateStudent,
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['students'] })
      queryClient.invalidateQueries({ queryKey: ['student', variables.uuid] })
    },
  })
}

export const useDeleteStudent = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: deleteStudent,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] })
    },
  })
}
