import { z } from 'zod'

export const createInvitationSchema = z.object({
  email: z.string().email('Please enter a valid email address'),
  role: z.enum(['owner', 'admin', 'hr', 'finance', 'employee'], {
    errorMap: () => ({ message: 'Please select a valid role' }),
  }),
})

export type CreateInvitationFormData = z.infer<typeof createInvitationSchema>
