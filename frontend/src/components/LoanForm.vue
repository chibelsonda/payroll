<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Employee</div>
        <v-select
          v-bind="createField('employee_uuid')"
          :items="employees || []"
          item-value="uuid"
          item-title="display_name"
          placeholder="Select employee"
          prepend-inner-icon="mdi-account"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field v-select"
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
          placeholder="Enter loan amount"
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
        <div class="text-body-2 mb-1 font-weight-medium">Balance</div>
        <v-text-field
          v-bind="createField('balance')"
          type="number"
          step="0.01"
          placeholder="Enter loan balance"
          prepend-inner-icon="mdi-cash-check"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Start Date</div>
        <v-text-field
          v-bind="createField('start_date')"
          type="date"
          placeholder="Select start date"
          prepend-inner-icon="mdi-calendar-start"
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
import { computed, watch, ref, nextTick } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { loanSchema, type LoanFormData } from '@/validation'
import { useCreateLoan, useUpdateLoan } from '@/composables/useLoans'
import { useEmployees } from '@/composables/useEmployees'
import type { Loan } from '@/types/loan'
import { useNotification } from '@/composables/useNotification'

interface Props {
  loan?: Loan | null
}

const props = withDefaults(defineProps<Props>(), {
  loan: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const { showNotification } = useNotification()
const createLoan = useCreateLoan()
const updateLoan = useUpdateLoan()
const { data: employeesData } = useEmployees()

const employees = computed(() => {
  if (!employeesData.value?.data) return []
  return employeesData.value.data.map((emp) => ({
    uuid: emp.uuid,
    display_name: `${emp.user?.first_name} ${emp.user?.last_name} (${emp.employee_no})`,
  }))
})

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm, values } = useZodForm(loanSchema, {
  employee_uuid: '',
  amount: '',
  balance: '',
  start_date: '',
})

// Create field refs for direct access
const amountField = createField('amount')
const balanceField = createField('balance')

// Watch for loan changes to populate form
watch(() => props.loan, (loan) => {
  if (loan) {
    setFieldValue('employee_uuid', loan.employee?.uuid || loan.employee_uuid || '')
    setFieldValue('amount', loan.amount || '')
    setFieldValue('balance', loan.balance || loan.amount || '')
    setFieldValue('start_date', loan.start_date || '')
  } else {
    resetForm()
  }
}, { immediate: true })

// Auto-set balance to amount when amount changes (for new loans)
// Use field.value directly to avoid recursive updates
const isUpdatingBalance = ref(false)
watch(() => amountField.value.value, (amount, oldAmount) => {
  if (!props.loan && amount && !isUpdatingBalance.value && amount !== oldAmount) {
    isUpdatingBalance.value = true
    setFieldValue('balance', amount)
    nextTick(() => {
      isUpdatingBalance.value = false
    })
  }
})

const handleSubmit = async () => {
  await handleFormSubmit(async (values: LoanFormData) => {
    try {
      if (props.loan) {
        await updateLoan.mutateAsync({
          uuid: props.loan.uuid,
          data: {
            amount: parseFloat(values.amount),
            balance: parseFloat(values.balance),
            start_date: values.start_date,
          },
        })
        showNotification('Loan updated successfully', 'success')
      } else {
        await createLoan.mutateAsync({
          employee_uuid: values.employee_uuid,
          amount: parseFloat(values.amount),
          balance: parseFloat(values.balance),
          start_date: values.start_date,
        })
        showNotification('Loan created successfully', 'success')
      }
      emit('submit')
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      const message = err?.response?.data?.message || 'Failed to save loan'
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
