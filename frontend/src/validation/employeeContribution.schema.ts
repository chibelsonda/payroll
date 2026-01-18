import { z } from 'zod'

export const employeeContributionSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  contribution_uuid: z.string().min(1, 'Contribution is required'),
})

export type EmployeeContributionFormData = z.infer<typeof employeeContributionSchema>
