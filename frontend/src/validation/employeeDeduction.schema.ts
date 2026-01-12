import { z } from 'zod'

export const employeeDeductionSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  deduction_uuid: z.string().min(1, 'Deduction is required'),
  amount: z.string().min(1, 'Amount is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num >= 0
  }, {
    message: 'Amount must be a non-negative number',
  }),
})

export type EmployeeDeductionFormData = z.infer<typeof employeeDeductionSchema>
