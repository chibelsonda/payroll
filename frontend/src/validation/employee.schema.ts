import { z } from 'zod'

export const employeeSchema = z.object({
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
  employee_no: z
    .string()
    .min(1, 'Employee No is required')
    .min(3, 'Employee No must be at least 3 characters')
    .max(20, 'Employee No must not exceed 20 characters')
    .regex(/^[A-Za-z0-9-]+$/, 'Employee No can only contain letters, numbers, and hyphens'),
  company_uuid: z.string().uuid().optional().nullable(),
  department_uuid: z.string().uuid().optional().nullable(),
  position_uuid: z.string().uuid().optional().nullable(),
  employment_type: z.enum(['regular', 'contractual', 'probationary']).optional().nullable(),
  hire_date: z.string().optional().nullable(),
  termination_date: z.string().optional().nullable(),
  status: z.enum(['active', 'inactive', 'terminated']).optional().nullable(),
  password: z
    .union([
      z.string().min(8, 'Password must be at least 8 characters').max(100, 'Password must not exceed 100 characters'),
      z.literal(''),
      z.undefined()
    ])
    .optional()
    .transform((val) => (val === '' ? undefined : val)),
})

// For create mode: password is required (no transform, validates empty strings)
export const createEmployeeSchema = employeeSchema.extend({
  password: z
    .string()
    .min(1, 'Password is required')
    .min(8, 'Password must be at least 8 characters')
    .max(100, 'Password must not exceed 100 characters'),
})

export const updateEmployeeSchema = employeeSchema.omit({ password: true })

export type EmployeeFormData = z.infer<typeof employeeSchema>
export type CreateEmployeeFormData = z.infer<typeof createEmployeeSchema>
export type UpdateEmployeeFormData = z.infer<typeof updateEmployeeSchema>
