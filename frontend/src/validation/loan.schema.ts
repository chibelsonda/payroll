import { z } from 'zod'

export const loanSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  amount: z.string().min(1, 'Amount is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num > 0
  }, {
    message: 'Amount must be a positive number',
  }),
  balance: z.string().min(1, 'Balance is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num >= 0
  }, {
    message: 'Balance must be a non-negative number',
  }),
  start_date: z.string().min(1, 'Start date is required'),
})

export type LoanFormData = z.infer<typeof loanSchema>
