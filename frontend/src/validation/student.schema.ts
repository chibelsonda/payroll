import { z } from 'zod'

export const studentSchema = z.object({
  first_name: z
    .string()
    .min(1, 'First name is required')
    .min(2, 'First name must be at least 2 characters')
    .max(50, 'First name must not exceed 50 characters')
    .regex(/^[a-zA-Z\s'-]+$/, 'First name can only contain letters, spaces, hyphens, and apostrophes'),
  last_name: z
    .string()
    .min(1, 'Last name is required')
    .min(2, 'Last name must be at least 2 characters')
    .max(50, 'Last name must not exceed 50 characters')
    .regex(/^[a-zA-Z\s'-]+$/, 'Last name can only contain letters, spaces, hyphens, and apostrophes'),
  email: z
    .string()
    .min(1, 'Email is required')
    .email('Please enter a valid email address')
    .max(255, 'Email must not exceed 255 characters'),
  student_id: z
    .string()
    .min(1, 'Student ID is required')
    .min(3, 'Student ID must be at least 3 characters')
    .max(20, 'Student ID must not exceed 20 characters')
    .regex(/^[A-Za-z0-9-]+$/, 'Student ID can only contain letters, numbers, and hyphens'),
  password: z
    .string()
    .min(1, 'Password is required')
    .min(8, 'Password must be at least 8 characters')
    .max(100, 'Password must not exceed 100 characters')
    .optional(),
})

export const createStudentSchema = studentSchema.required({ password: true })

export const updateStudentSchema = studentSchema.omit({ password: true })

export type StudentFormData = z.infer<typeof studentSchema>
export type CreateStudentFormData = z.infer<typeof createStudentSchema>
export type UpdateStudentFormData = z.infer<typeof updateStudentSchema>
