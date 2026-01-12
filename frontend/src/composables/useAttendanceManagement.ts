import { useMutation, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Attendance } from '@/types/attendance'
import type { AttendanceLog } from '@/types/attendanceLog'

// Employee Actions
const requestCorrection = async (data: {
  attendance_uuid: string
  reason: string
}): Promise<Attendance> => {
  const response = await axios.post('/attendance/correction-request', data)
  return response.data.data
}

// Admin Actions
const updateAttendanceLog = async (logUuid: string, data: {
  log_time: string
  type: 'IN' | 'OUT'
}): Promise<AttendanceLog> => {
  const response = await axios.put(`/attendance/logs/${logUuid}`, data)
  return response.data.data
}

const recalculateAttendance = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post('/attendance/recalculate', { attendance_uuid: attendanceUuid })
  return response.data.data
}

const approveAttendance = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post('/attendance/approve', { attendance_uuid: attendanceUuid })
  return response.data.data
}

const markIncomplete = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post('/attendance/mark-incomplete', { attendance_uuid: attendanceUuid })
  return response.data.data
}

const lockAttendance = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post('/attendance/lock', { attendance_uuid: attendanceUuid })
  return response.data.data
}

// Composables
export const useRequestCorrection = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: requestCorrection,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-needing-review'] })
    },
  })
}

export const useUpdateAttendanceLog = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ logUuid, data }: { logUuid: string; data: { log_time: string; type: 'IN' | 'OUT' } }) =>
      updateAttendanceLog(logUuid, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}

export const useRecalculateAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: recalculateAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
    },
  })
}

export const useApproveAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: approveAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-needing-review'] })
    },
  })
}

export const useMarkIncomplete = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: markIncomplete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-needing-review'] })
    },
  })
}

export const useLockAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: lockAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}
