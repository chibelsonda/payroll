import { z } from 'zod'

export const payrollRunSchema = z.object({
  company_uuid: z.string().uuid('Invalid company'),
  period_start: z.string().min(1, 'Period start is required'),
  period_end: z.string().min(1, 'Period end is required'),
  pay_date: z.string().min(1, 'Pay date is required'),
  status: z.enum(['draft', 'processed', 'paid']).optional().default('draft'),
}).refine((data) => {
  return new Date(data.period_end) >= new Date(data.period_start)
}, {
  message: 'Period end must be after or equal to period start',
  path: ['period_end'],
}).refine((data) => {
  return new Date(data.pay_date) >= new Date(data.period_end)
}, {
  message: 'Pay date must be after or equal to period end',
  path: ['pay_date'],
})

export type PayrollRunFormData = z.infer<typeof payrollRunSchema>
