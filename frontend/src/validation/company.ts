import { z } from 'zod'

export const createCompanySchema = z.object({
  name: z.string().min(1, 'Company name is required').max(255, 'Company name is too long'),
  registration_no: z.string().max(255, 'Registration number is too long').optional().nullable(),
  address: z.string().max(500, 'Address is too long').optional().nullable(),
})

export type CreateCompanyFormData = z.infer<typeof createCompanySchema>
