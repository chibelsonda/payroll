import { z } from 'zod'

export const contributionSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  employee_share: z.string().min(1, 'Employee share is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num >= 0 && num <= 100
  }, {
    message: 'Employee share must be between 0 and 100',
  }),
  employer_share: z.string().min(1, 'Employer share is required').refine((val) => {
    const num = parseFloat(val)
    return !isNaN(num) && num >= 0 && num <= 100
  }, {
    message: 'Employer share must be between 0 and 100',
  }),
})

export type ContributionFormData = z.infer<typeof contributionSchema>
