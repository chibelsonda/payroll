# Form Validation Guide - Zod + VeeValidate

## Overview

This project uses **Zod** for schema validation and **VeeValidate** for form state management, providing type-safe, declarative form validation throughout the application.

## 📦 Packages Installed

- `zod` - Schema validation library
- `vee-validate` - Form state management
- `@vee-validate/zod` - Zod integration for VeeValidate

## 📁 File Structure

```
src/
├── validation/
│   ├── index.ts              # Export all schemas
│   ├── login.schema.ts        # Login form schema
│   ├── register.schema.ts     # Registration form schema
│   ├── student.schema.ts       # Student form schemas
│   └── subject.schema.ts       # Subject form schemas
└── composables/
    └── useZodForm.ts          # Reusable Zod form composable
```

---

## 🔧 Zod Schemas

### 1. Login Schema (`login.schema.ts`)

```typescript
import { z } from 'zod'

export const loginSchema = z.object({
  email: z
    .string({ required_error: 'Email is required' })
    .min(1, 'Email is required')
    .email('Please enter a valid email address'),
  password: z
    .string({ required_error: 'Password is required' })
    .min(1, 'Password is required')
    .min(8, 'Password must be at least 8 characters'),
})

export type LoginFormData = z.infer<typeof loginSchema>
```

**Validation Rules:**
- ✅ Email: Required, must be valid email format
- ✅ Password: Required, minimum 8 characters

---

### 2. Register Schema (`register.schema.ts`)

```typescript
import { z } from 'zod'

export const registerSchema = z
  .object({
    first_name: z
      .string({ required_error: 'First name is required' })
      .min(1, 'First name is required')
      .min(2, 'First name must be at least 2 characters')
      .max(50, 'First name must not exceed 50 characters')
      .regex(/^[a-zA-Z\s'-]+$/, 'First name can only contain letters, spaces, hyphens, and apostrophes'),
    last_name: z
      .string({ required_error: 'Last name is required' })
      .min(1, 'Last name is required')
      .min(2, 'Last name must be at least 2 characters')
      .max(50, 'Last name must not exceed 50 characters')
      .regex(/^[a-zA-Z\s'-]+$/, 'Last name can only contain letters, spaces, hyphens, and apostrophes'),
    email: z
      .string({ required_error: 'Email is required' })
      .min(1, 'Email is required')
      .email('Please enter a valid email address')
      .max(255, 'Email must not exceed 255 characters'),
    password: z
      .string({ required_error: 'Password is required' })
      .min(1, 'Password is required')
      .min(8, 'Password must be at least 8 characters')
      .max(100, 'Password must not exceed 100 characters')
      .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
      .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
      .regex(/[0-9]/, 'Password must contain at least one number')
      .regex(/[^A-Za-z0-9]/, 'Password must contain at least one special character'),
    password_confirmation: z
      .string({ required_error: 'Password confirmation is required' })
      .min(1, 'Password confirmation is required'),
    role: z
      .enum(['student', 'admin'], {
        errorMap: () => ({ message: 'Role must be either student or admin' }),
      })
      .default('student'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

export type RegisterFormData = z.infer<typeof registerSchema>
```

**Validation Rules:**
- ✅ First/Last Name: 2-50 chars, letters/spaces/hyphens/apostrophes only
- ✅ Email: Valid email format, max 255 chars
- ✅ Password: 8-100 chars, must contain uppercase, lowercase, number, and special character
- ✅ Password Confirmation: Must match password
- ✅ Role: Enum (student/admin), defaults to 'student'

---

### 3. Student Schema (`student.schema.ts`)

```typescript
import { z } from 'zod'

export const studentSchema = z.object({
  first_name: z.string().min(2).max(50).regex(/^[a-zA-Z\s'-]+$/),
  last_name: z.string().min(2).max(50).regex(/^[a-zA-Z\s'-]+$/),
  email: z.string().email().max(255),
  student_id: z.string().min(3).max(20).regex(/^[A-Za-z0-9-]+$/),
  password: z.string().min(8).max(100).optional(),
})

export const createStudentSchema = studentSchema.required({ password: true })
export const updateStudentSchema = studentSchema.omit({ password: true })
```

**Schemas:**
- `studentSchema` - Base schema
- `createStudentSchema` - Password required for creation
- `updateStudentSchema` - Password omitted for updates

---

### 4. Subject Schema (`subject.schema.ts`)

```typescript
import { z } from 'zod'

export const subjectSchema = z.object({
  code: z
    .string({ required_error: 'Subject code is required' })
    .min(2, 'Subject code must be at least 2 characters')
    .max(20, 'Subject code must not exceed 20 characters')
    .regex(/^[A-Z0-9-]+$/, 'Subject code must be uppercase letters, numbers, and hyphens only')
    .transform((val) => val.toUpperCase()),
  name: z
    .string({ required_error: 'Subject name is required' })
    .min(3, 'Subject name must be at least 3 characters')
    .max(100, 'Subject name must not exceed 100 characters'),
  description: z
    .string()
    .max(500, 'Description must not exceed 500 characters')
    .optional()
    .or(z.literal('')),
  credits: z
    .number({ required_error: 'Credits is required' })
    .int('Credits must be a whole number')
    .min(1, 'Credits must be at least 1')
    .max(10, 'Credits must not exceed 10'),
})
```

**Features:**
- ✅ Code auto-uppercases on transform
- ✅ Description optional
- ✅ Credits: Integer, 1-10 range

---

## 🎯 Reusable Composable: `useZodForm`

### Location: `src/composables/useZodForm.ts`

```typescript
import { computed } from 'vue'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import type { ZodSchema } from 'zod'

export function useZodForm<T extends Record<string, any>>(
  schema: ZodSchema<T>,
  initialValues?: Partial<T>
) {
  const form = useForm<T>({
    validationSchema: toTypedSchema(schema),
    initialValues: initialValues as T,
  })

  const createField = (name: keyof T) => {
    const field = useField(name, undefined, {
      validateOnValueUpdate: true,
    })

    return {
      ...field,
      errorMessage: computed(() => field.errorMessage.value),
      hasError: computed(() => !!field.errorMessage.value),
    }
  }

  const setServerErrors = (errors: Record<string, string | string[]>) => {
    Object.keys(errors).forEach((key) => {
      const fieldName = key as keyof T
      const errorMessages = Array.isArray(errors[key]) 
        ? (errors[key] as string[]).join(', ') 
        : errors[key]
      
      form.setFieldError(fieldName, errorMessages as string)
    })
  }

  const clearServerErrors = () => {
    form.clearErrors()
  }

  const resetForm = () => {
    form.resetForm({ values: initialValues as T })
  }

  return {
    // Form state
    form,
    values: form.values,
    errors: form.errors,
    meta: form.meta,
    isSubmitting: form.isSubmitting,
    isValid: form.meta.value.valid,
    
    // Form methods
    handleSubmit: form.handleSubmit,
    setFieldValue: form.setFieldValue,
    setFieldError: form.setFieldError,
    validate: form.validate,
    resetForm,
    
    // Helpers
    createField,
    setServerErrors,
    clearServerErrors,
  }
}
```

**Features:**
- ✅ Type-safe with TypeScript generics
- ✅ Automatic error mapping
- ✅ Server-side error integration
- ✅ Form state management
- ✅ Validation helpers

---

## 📝 Usage Examples

### Example 1: Login Form (`Login.vue`)

```vue
<template>
  <AuthFormCard title="Login" :error="errorMessage" @clear-error="clearError">
    <v-form @submit="onSubmit">
      <v-text-field
        v-model="email"
        name="email"
        label="Email"
        type="email"
        :error-messages="emailError"
        :error="hasEmailError"
        required
      ></v-text-field>

      <v-text-field
        v-model="password"
        name="password"
        label="Password"
        :type="showPassword ? 'text' : 'password'"
        :error-messages="passwordError"
        :error="hasPasswordError"
        required
      ></v-text-field>

      <v-btn
        type="submit"
        :loading="auth.isLoginLoading || isSubmitting"
        :disabled="!isValid"
      >
        Login
      </v-btn>
    </v-form>
  </AuthFormCard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { loginSchema, type LoginFormData } from '@/validation'

const { handleSubmit, createField, isSubmitting, isValid, setServerErrors } = 
  useZodForm<LoginFormData>(loginSchema, { email: '', password: '' })

const emailField = createField('email')
const passwordField = createField('password')

const email = computed({
  get: () => emailField.value.value as string,
  set: (value: string) => emailField.setValue(value),
})

const password = computed({
  get: () => passwordField.value.value as string,
  set: (value: string) => passwordField.setValue(value),
})

const emailError = computed(() => emailField.errorMessage.value)
const passwordError = computed(() => passwordField.errorMessage.value)
const hasEmailError = computed(() => !!emailError.value)
const hasPasswordError = computed(() => !!passwordError.value)

const onSubmit = handleSubmit(async (values: unknown) => {
  const formData = values as LoginFormData
  try {
    await auth.login(formData)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { errors?: Record<string, string | string[]> } } }
    if (err?.response?.data?.errors) {
      setServerErrors(err.response.data.errors)
    }
  }
})
</script>
```

---

### Example 2: Register Form (`Register.vue`)

```vue
<template>
  <AuthFormCard title="Register" :error="errorMessage" @clear-error="clearError">
    <v-form @submit="onSubmit">
      <v-text-field
        v-model="firstName"
        name="first_name"
        label="First Name"
        :error-messages="firstNameError"
        :error="hasFirstNameError"
        required
      ></v-text-field>

      <v-text-field
        v-model="lastName"
        name="last_name"
        label="Last Name"
        :error-messages="lastNameError"
        :error="hasLastNameError"
        required
      ></v-text-field>

      <v-text-field
        v-model="email"
        name="email"
        label="Email"
        type="email"
        :error-messages="emailError"
        :error="hasEmailError"
        required
      ></v-text-field>

      <v-text-field
        v-model="password"
        name="password"
        label="Password"
        :type="showPassword ? 'text' : 'password'"
        :error-messages="passwordError"
        :error="hasPasswordError"
        hint="Must contain uppercase, lowercase, number, and special character"
        persistent-hint
        required
      ></v-text-field>

      <v-text-field
        v-model="passwordConfirmation"
        name="password_confirmation"
        label="Confirm Password"
        :type="showPasswordConfirm ? 'text' : 'password'"
        :error-messages="passwordConfirmationError"
        :error="hasPasswordConfirmationError"
        required
      ></v-text-field>

      <v-btn
        type="submit"
        :loading="auth.isRegisterLoading || isSubmitting"
        :disabled="!isValid"
      >
        Register
      </v-btn>
    </v-form>
  </AuthFormCard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { registerSchema, type RegisterFormData } from '@/validation'

const { handleSubmit, createField, isSubmitting, isValid, setServerErrors } = 
  useZodForm<RegisterFormData>(registerSchema, {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student',
  })

// Create all fields
const firstNameField = createField('first_name')
const lastNameField = createField('last_name')
const emailField = createField('email')
const passwordField = createField('password')
const passwordConfirmationField = createField('password_confirmation')

// Bind values (similar pattern for all fields)
const firstName = computed({
  get: () => firstNameField.value.value as string,
  set: (value: string) => firstNameField.setValue(value),
})

// ... (similar for other fields)

// Error messages
const firstNameError = computed(() => firstNameField.errorMessage.value)
// ... (similar for other fields)

const onSubmit = handleSubmit(async (values: unknown) => {
  const formData = values as RegisterFormData
  try {
    await auth.register(formData)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { errors?: Record<string, string | string[]> } } }
    if (err?.response?.data?.errors) {
      setServerErrors(err.response.data.errors)
    }
  }
})
</script>
```

---

## ✨ Key Features

### 1. **Type Safety**
- ✅ Full TypeScript support
- ✅ Inferred types from Zod schemas
- ✅ Compile-time type checking

### 2. **Client-Side Validation**
- ✅ Real-time validation
- ✅ Custom error messages
- ✅ Field-level validation

### 3. **Server-Side Error Integration**
- ✅ Merge API validation errors
- ✅ Display server errors in form
- ✅ Clear server errors on input

### 4. **Vuetify Integration**
- ✅ Error messages under inputs
- ✅ Error states (red borders)
- ✅ Disabled submit when invalid
- ✅ Loading states

### 5. **User Experience**
- ✅ Submit blocked if invalid
- ✅ Clear error messages
- ✅ Persistent hints
- ✅ Password visibility toggles

---

## 🎯 Best Practices

1. **Always use Zod schemas** - Never validate manually
2. **Use `useZodForm` composable** - Don't create forms from scratch
3. **Handle server errors** - Use `setServerErrors()` for API errors
4. **Type your forms** - Use inferred types from schemas
5. **Show helpful errors** - Custom error messages improve UX

---

## 🔍 Testing Validation

### Test Login Form:
1. Submit empty form → Should show "Email is required"
2. Enter invalid email → Should show "Please enter a valid email address"
3. Enter short password → Should show "Password must be at least 8 characters"
4. Enter valid data → Form should submit

### Test Register Form:
1. Test password requirements (uppercase, lowercase, number, special char)
2. Test password match validation
3. Test name regex validation
4. Test email format validation

---

## 📚 Resources

- [Zod Documentation](https://zod.dev/)
- [VeeValidate Documentation](https://vee-validate.logaretm.com/v4/)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

---

**Last Updated**: January 7, 2026
