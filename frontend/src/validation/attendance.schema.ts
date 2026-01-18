import { z } from 'zod'

export const attendanceSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  date: z.string().min(1, 'Date is required'),
  time_in: z.string().optional(),
  time_out: z.string().optional(),
  hours_worked: z.string().optional(),
})

export type AttendanceFormData = z.infer<typeof attendanceSchema>
