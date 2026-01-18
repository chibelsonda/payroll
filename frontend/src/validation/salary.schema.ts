import { z } from 'zod'

export const salarySchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  amount: z.string().min(1, 'Amount is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num > 0
  }, {
    message: 'Amount must be a positive number',
  }),
  effective_from: z.string().min(1, 'Effective date is required'),
})

export type SalaryFormData = z.infer<typeof salarySchema>
