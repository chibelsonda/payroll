import { computed } from 'vue'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import type { ZodSchema } from 'zod'

/**
 * Reusable composable for Zod form validation with VeeValidate
 *
 * @template T - The type inferred from the Zod schema
 * @param schema - Zod schema for validation
 * @param initialValues - Initial form values (optional)
 * @returns Form context with validation helpers
 */
export function useZodForm<T extends Record<string, unknown>>(
  schema: ZodSchema<T>,
  initialValues?: Partial<T>
) {
  const form = useForm<T>({
    validationSchema: toTypedSchema(schema),
    // @ts-expect-error - VeeValidate expects deep partial but we provide compatible Partial<T>
    initialValues: initialValues || {},
    validateOnMount: false,
    validateOnBlur: true,
    validateOnChange: true,
  })

  /**
   * Create a field with automatic error mapping
   */
  const createField = (name: keyof T) => {
    const field = useField(String(name), undefined, {
      validateOnValueUpdate: true,
    })

    return {
      ...field,
      // Map VeeValidate errors to Vuetify format
      errorMessage: computed(() => field.errorMessage.value),
      hasError: computed(() => !!field.errorMessage.value),
    }
  }

  /**
   * Set server-side validation errors
   * Useful for handling API validation errors
   */
  const setServerErrors = (errors: Record<string, string | string[]>) => {
    Object.keys(errors).forEach((key) => {
      const errorMessages = Array.isArray(errors[key])
        ? (errors[key] as string[]).join(', ')
        : errors[key]

      form.setFieldError(key as never, errorMessages as string)
    })
  }

  /**
   * Clear all server errors without resetting form values
   */
  const clearServerErrors = () => {
    // Clear errors for all fields without resetting values
    Object.keys(form.values.value || {}).forEach((key) => {
      form.setFieldError(key as never, undefined)
    })
  }

  /**
   * Reset form to initial values
   */
  const resetForm = () => {
    // @ts-expect-error - VeeValidate expects deep partial but we provide compatible Partial<T>
    form.resetForm({ values: initialValues || {} })
  }

  return {
    // Form state
    form,
    values: form.values,
    errors: form.errors,
    meta: form.meta,
    isSubmitting: form.isSubmitting,
    isValid: form.meta.value.valid,

    // Form methods - properly typed
    handleSubmit: form.handleSubmit as <TSubmit extends (values: T) => void | Promise<void>>(
      callback: TSubmit
    ) => (e?: Event) => Promise<void>,
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

