<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="500"
      class="drawer payroll-run-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <!-- Enhanced Header -->
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar
            color="primary"
            size="40"
            class="me-3"
          >
            <v-icon color="white">mdi-cash-register</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">Generate Payroll</div>
            <div class="text-caption text-medium-emphasis mt-1">
              Create a new payroll run for employees
            </div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="$emit('update:modelValue', false)"
            class="ml-2"
          ></v-btn>
        </div>
      </v-card-title>

      <v-divider class="flex-shrink-0"></v-divider>

      <v-form ref="formRef" @submit.prevent="onSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
        <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
          <!-- Company Selection -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Company</div>
            <v-select
              v-model="companyUuid"
              :items="companies || []"
              item-value="uuid"
              item-title="name"
              placeholder="Select company"
              prepend-inner-icon="mdi-office-building"
              :error-messages="companyUuidError"
              :error="hasCompanyUuidError"
              density="compact"
              variant="outlined"
              hide-details="auto"
              class="employee-form-field v-select"
              clearable
            ></v-select>
          </div>

          <!-- Period Start -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Period Start</div>
            <v-text-field
              v-model="periodStart"
              type="date"
              placeholder="Select start date"
              prepend-inner-icon="mdi-calendar-start"
              :error-messages="periodStartError"
              :error="hasPeriodStartError"
              density="compact"
              variant="outlined"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Period End -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Period End</div>
            <v-text-field
              v-model="periodEnd"
              type="date"
              placeholder="Select end date"
              prepend-inner-icon="mdi-calendar-end"
              :error-messages="periodEndError"
              :error="hasPeriodEndError"
              density="compact"
              variant="outlined"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Pay Date -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Pay Date</div>
            <v-text-field
              v-model="payDate"
              type="date"
              placeholder="Select pay date"
              prepend-inner-icon="mdi-calendar-check"
              :error-messages="payDateError"
              :error="hasPayDateError"
              density="compact"
              variant="outlined"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>
        </v-card-text>

        <v-divider class="flex-shrink-0"></v-divider>

        <!-- Actions -->
        <v-card-actions class="pa-4 flex-shrink-0 bg-grey-lighten-5">
          <v-btn
            type="button"
            variant="outlined"
            @click="$emit('update:modelValue', false)"
            class="flex-grow-1"
            size="small"
          >
            Cancel
          </v-btn>
          <v-spacer class="mx-2"></v-spacer>
          <v-btn
            type="submit"
            color="primary"
            variant="flat"
            size="small"
            :loading="createMutation.isPending.value"
            class="flex-grow-1"
            prepend-icon="mdi-content-save"
          >
            Create Payroll Run
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useCreatePayrollRun } from '@/composables'
import { useCompanies } from '@/composables'
import { useZodForm } from '@/composables'
import { payrollRunSchema, type PayrollRunFormData } from '@/validation/payroll.schema'
import { useNotification } from '@/composables'
import type { Company } from '@/types/company'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
}>()

const notification = useNotification()
const formRef = ref()
const createMutation = useCreatePayrollRun()
const { data: companies } = useCompanies()

const {
  createField,
  handleSubmit,
  setFieldValue,
  resetForm,
  errors,
} = useZodForm(payrollRunSchema, {
  company_uuid: '',
  period_start: '',
  period_end: '',
  pay_date: '',
  status: 'draft',
})

// Create fields with validation
const companyUuidField = createField('company_uuid')
const periodStartField = createField('period_start')
const periodEndField = createField('period_end')
const payDateField = createField('pay_date')

// Bind field values
const companyUuid = computed({
  get: () => (companyUuidField.value.value as string) || '',
  set: (value: string) => companyUuidField.setValue(value),
})

const periodStart = computed({
  get: () => (periodStartField.value.value as string) || '',
  set: (value: string) => periodStartField.setValue(value),
})

const periodEnd = computed({
  get: () => (periodEndField.value.value as string) || '',
  set: (value: string) => periodEndField.setValue(value),
})

const payDate = computed({
  get: () => (payDateField.value.value as string) || '',
  set: (value: string) => payDateField.setValue(value),
})

const companyUuidError = computed(() => companyUuidField.errorMessage.value)
const hasCompanyUuidError = computed(() => !!companyUuidError.value)
const periodStartError = computed(() => periodStartField.errorMessage.value)
const hasPeriodStartError = computed(() => !!periodStartError.value)
const periodEndError = computed(() => periodEndField.errorMessage.value)
const hasPeriodEndError = computed(() => !!periodEndError.value)
const payDateError = computed(() => payDateField.errorMessage.value)
const hasPayDateError = computed(() => !!payDateError.value)

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    resetForm()
  }
})

const onSubmit = handleSubmit(async (formData: PayrollRunFormData) => {
  try {
    await createMutation.mutateAsync(formData)
    notification.showSuccess('Payroll run created successfully')
    emit('update:modelValue', false)
    emit('success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to create payroll run'
    notification.showError(message)
  }
})
</script>

