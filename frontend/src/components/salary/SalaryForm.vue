<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Amount</div>
        <v-text-field
          :model-value="amountField.value"
          @update:model-value="amountField.onChange"
          :error-messages="amountField.error"
          type="number"
          step="0.01"
          placeholder="Enter salary amount"
          prepend-inner-icon="mdi-cash"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Effective From</div>
        <v-text-field
          :model-value="dateField.value"
          @update:model-value="dateField.onChange"
          :error-messages="dateField.error"
          type="date"
          placeholder="Select effective date"
          prepend-inner-icon="mdi-calendar"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
import { watch, computed } from 'vue'
import { useZodForm } from '@/composables'
import { salarySchema, type SalaryFormData } from '@/validation'
import { useCreateSalary, useUpdateSalary } from '@/composables'
import type { Salary } from '@/types/salary'
import { useNotification } from '@/composables'

/* ---------------- PROPS ---------------- */
interface Props {
  salary?: Salary | null
  selectedEmployeeUuid?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  salary: null,
  selectedEmployeeUuid: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

/* ---------------- SERVICES ---------------- */
const { showNotification } = useNotification()
const createSalary = useCreateSalary()
const updateSalary = useUpdateSalary()

/* ---------------- FORM ---------------- */
const {
  createField,
  handleSubmit: handleFormSubmit,
  isSubmitting,
  isValid,
  setFieldValue,
  resetForm,
} = useZodForm(salarySchema, {
  employee_uuid: props.selectedEmployeeUuid || '',
  amount: props.salary?.amount?.toString() || '',
  effective_from: props.salary?.effective_from || '',
})

/* ---------------- SAFE FIELD BINDINGS ---------------- */
const amountField = computed(() => {
  const f = createField('amount')

  return {
    value: String(f?.value?.value ?? f?.value ?? ''),
    onChange: f?.handleChange ?? (() => {}),
    error: String(f?.errorMessage?.value ?? f?.errorMessage ?? ''),
  }
})

const dateField = computed(() => {
  const f = createField('effective_from')

  return {
    value: String(f?.value?.value ?? f?.value ?? ''),
    onChange: f?.handleChange ?? (() => {}),
    error: String(f?.errorMessage?.value ?? f?.errorMessage ?? ''),
  }
})

/* ---------------- WATCHERS ---------------- */
watch(
  () => props.salary,
  (salary) => {
    if (salary) {
      setFieldValue('amount', salary.amount?.toString() || '')
      setFieldValue('effective_from', salary.effective_from || '')
    } else {
      resetForm()
      if (props.selectedEmployeeUuid) {
        setFieldValue('employee_uuid', props.selectedEmployeeUuid)
      }
    }
  },
  { immediate: true }
)

watch(
  () => props.selectedEmployeeUuid,
  (employeeUuid) => {
    if (employeeUuid && !props.salary) {
      setFieldValue('employee_uuid', employeeUuid)
    }
  },
  { immediate: true }
)

/* ---------------- SUBMIT ---------------- */
const handleSubmit = async () => {
  await handleFormSubmit(async (formValues: SalaryFormData) => {
    try {
      const payload = {
        employee_uuid: props.selectedEmployeeUuid || formValues.employee_uuid,
        amount: parseFloat(String(formValues.amount)),
        effective_from: formValues.effective_from,
      }

      if (props.salary) {
        await updateSalary.mutateAsync({
          uuid: props.salary.uuid,
          data: {
            amount: payload.amount,
            effective_from: payload.effective_from,
          },
        })
        showNotification('Salary updated successfully', 'success')
      } else {
        await createSalary.mutateAsync(payload)
        showNotification('Salary created successfully', 'success')
      }

      emit('submit')
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      const message = err?.response?.data?.message || 'Failed to save salary'
      showNotification(message, 'error')
      throw error
    }
  })
}

/* ---------------- EXPOSE ---------------- */
defineExpose({
  handleSubmit,
  isSubmitting,
  isValid,
})
</script>
