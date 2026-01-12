<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Amount</div>
        <v-text-field
          v-bind="createField('amount')"
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
          v-bind="createField('effective_from')"
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
import { useZodForm } from '@/composables/useZodForm'
import { salarySchema, type SalaryFormData } from '@/validation'
import { useCreateSalary, useUpdateSalary } from '@/composables/useSalaries'
import type { Salary } from '@/types/salary'
import { useNotification } from '@/composables/useNotification'

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

const { showNotification } = useNotification()
const createSalary = useCreateSalary()
const updateSalary = useUpdateSalary()

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm, values } = useZodForm(salarySchema, {
  employee_uuid: props.selectedEmployeeUuid || '',
  amount: props.salary?.amount || '',
  effective_from: props.salary?.effective_from || '',
})

// Watch for salary changes to populate form
watch(() => props.salary, (salary) => {
  if (salary) {
    setFieldValue('amount', salary.amount)
    setFieldValue('effective_from', salary.effective_from)
  } else {
    resetForm()
    if (props.selectedEmployeeUuid) {
      setFieldValue('employee_uuid', props.selectedEmployeeUuid)
    }
  }
}, { immediate: true })

// Watch for selectedEmployeeUuid changes
watch(() => props.selectedEmployeeUuid, (employeeUuid) => {
  if (employeeUuid && !props.salary) {
    setFieldValue('employee_uuid', employeeUuid)
  }
}, { immediate: true })

const handleSubmit = async () => {
  await handleFormSubmit(async (formValues: SalaryFormData) => {
    try {
      const payload = {
        employee_uuid: props.selectedEmployeeUuid || formValues.employee_uuid,
        amount: parseFloat(formValues.amount),
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

// Expose submit handler and state to parent
defineExpose({
  handleSubmit,
  isSubmitting,
  isValid,
})
</script>
