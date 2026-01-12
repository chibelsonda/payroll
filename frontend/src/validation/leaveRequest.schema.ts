import { z } from 'zod'

export const leaveRequestSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  leave_type: z.enum(['vacation', 'sick'], {
    required_error: 'Leave type is required',
  }),
  start_date: z.string().min(1, 'Start date is required'),
  end_date: z.string().min(1, 'End date is required'),
}).refine((data) => {
  return new Date(data.end_date) >= new Date(data.start_date)
}, {
  message: 'End date must be after or equal to start date',
  path: ['end_date'],
})

export type LeaveRequestFormData = z.infer<typeof leaveRequestSchema>
