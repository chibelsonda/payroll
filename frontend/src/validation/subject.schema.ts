import { z } from 'zod'

export const subjectSchema = z.object({
  code: z
    .string()
    .min(1, 'Subject code is required')
    .min(2, 'Subject code must be at least 2 characters')
    .max(20, 'Subject code must not exceed 20 characters')
    .regex(/^[A-Z0-9-]+$/, 'Subject code must be uppercase letters, numbers, and hyphens only')
    .transform((val) => val.toUpperCase()),
  name: z
    .string()
    .min(1, 'Subject name is required')
    .min(3, 'Subject name must be at least 3 characters')
    .max(100, 'Subject name must not exceed 100 characters'),
  description: z
    .string()
    .max(500, 'Description must not exceed 500 characters')
    .optional()
    .or(z.literal('')),
  credits: z
    .number()
    .int('Credits must be a whole number')
    .min(1, 'Credits is required and must be at least 1')
    .max(10, 'Credits must not exceed 10'),
})

export const createSubjectSchema = subjectSchema

export const updateSubjectSchema = subjectSchema.partial()

export type SubjectFormData = z.infer<typeof subjectSchema>
export type CreateSubjectFormData = z.infer<typeof createSubjectSchema>
export type UpdateSubjectFormData = z.infer<typeof updateSubjectSchema>
