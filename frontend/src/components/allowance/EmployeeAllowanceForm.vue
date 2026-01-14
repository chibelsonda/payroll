<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Employee</div>
        <v-select
          v-bind="createField('employee_uuid')"
          :items="employeeOptions"
          placeholder="Select employee"
          prepend-inner-icon="mdi-account"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
          :disabled="!!allowance?.employee_uuid"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Type</div>
        <v-select
          v-bind="createField('type')"
          :items="typeOptions"
          placeholder="Select allowance type"
          prepend-inner-icon="mdi-format-list-bulleted-type"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Description</div>
        <v-text-field
          v-bind="createField('description')"
          placeholder="Enter description (optional)"
          prepend-inner-icon="mdi-text"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Amount</div>
        <v-text-field
          v-bind="createField('amount')"
          type="number"
          step="0.01"
          placeholder="Enter amount"
          prepend-inner-icon="mdi-cash"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Effective From</div>
        <v-text-field
          v-bind="createField('effective_from')"
          type="date"
          placeholder="Select start date (optional)"
          prepend-inner-icon="mdi-calendar"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Effective To</div>
        <v-text-field
          v-bind="createField('effective_to')"
          type="date"
          placeholder="Select end date (optional)"
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
import { z } from 'zod'
import type { EmployeeAllowance } from '@/types/allowance'
import { useEmployees } from '@/composables'

interface Props {
  allowance?: EmployeeAllowance | null
  selectedEmployeeUuid?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  allowance: null,
  selectedEmployeeUuid: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const { data: employeesData } = useEmployees()

const employeeOptions = computed(() => {
  if (!employeesData.value?.data) return []
  return employeesData.value.data.map((emp) => ({
    title: `${emp.user?.first_name} ${emp.user?.last_name} (${emp.employee_no})`,
    value: emp.uuid,
  }))
})

const allowanceSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  type: z.string().min(1, 'Type is required'),
  description: z.string().optional(),
  amount: z.string().min(1, 'Amount is required').refine((val) => !isNaN(parseFloat(val)) && parseFloat(val) > 0, {
    message: 'Amount must be a positive number',
  }),
  effective_from: z.string().optional(),
  effective_to: z.string().optional(),
})

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm } = useZodForm(
  allowanceSchema,
  {
    employee_uuid: props.selectedEmployeeUuid || props.allowance?.employee_uuid || '',
    type: props.allowance?.type || '',
    description: props.allowance?.description || '',
    amount: props.allowance?.amount?.toString() || '',
    effective_from: props.allowance?.effective_from || '',
    effective_to: props.allowance?.effective_to || '',
  }
)

const typeOptions = [
  { title: 'Transport', value: 'transport' },
  { title: 'Meal', value: 'meal' },
  { title: 'Housing', value: 'housing' },
  { title: 'Medical', value: 'medical' },
  { title: 'Other', value: 'other' },
]

watch(() => props.allowance, (allowance) => {
  if (allowance) {
    setFieldValue('employee_uuid', allowance.employee_uuid || '')
    setFieldValue('type', allowance.type || '')
    setFieldValue('description', allowance.description || '')
    setFieldValue('amount', allowance.amount?.toString() || '')
    setFieldValue('effective_from', allowance.effective_from || '')
    setFieldValue('effective_to', allowance.effective_to || '')
  } else {
    resetForm()
    if (props.selectedEmployeeUuid) {
      setFieldValue('employee_uuid', props.selectedEmployeeUuid)
    }
  }
})

watch(() => props.selectedEmployeeUuid, (uuid) => {
  if (uuid && !props.allowance) {
    setFieldValue('employee_uuid', uuid)
  }
})

defineExpose({
  handleSubmit: handleFormSubmit,
  isSubmitting,
  isValid,
  values: computed(() => ({
    employee_uuid: createField('employee_uuid').modelValue.value,
    type: createField('type').modelValue.value,
    description: createField('description').modelValue.value,
    amount: parseFloat(createField('amount').modelValue.value || '0'),
    effective_from: createField('effective_from').modelValue.value || null,
    effective_to: createField('effective_to').modelValue.value || null,
  })),
})
</script>
