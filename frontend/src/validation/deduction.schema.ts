import { z } from 'zod'

export const deductionSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  type: z.enum(['fixed', 'percentage'], {
    required_error: 'Type is required',
  }),
})

export type DeductionFormData = z.infer<typeof deductionSchema>
