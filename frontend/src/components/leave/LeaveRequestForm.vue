<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="500"
    class="drawer leave-request-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar color="primary" size="40" class="me-3">
            <v-icon color="white">mdi-calendar-remove</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">Request Leave</div>
            <div class="text-caption text-medium-emphasis mt-1">
              Submit a leave request for approval
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
          <!-- Leave Type -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Leave Type</div>
            <v-select
              v-model="leaveTypeField.value.value"
              :error-messages="leaveTypeField.errorMessage.value"
              :items="leaveTypes"
              placeholder="Select leave type"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-calendar-question"
              hide-details="auto"
              class="employee-form-field v-select"
            ></v-select>
          </div>

          <!-- Start Date -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Start Date</div>
            <v-text-field
              v-model="startDateField.value.value"
              :error-messages="startDateField.errorMessage.value"
              type="date"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-calendar-start"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- End Date -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">End Date</div>
            <v-text-field
              v-model="endDateField.value.value"
              :error-messages="endDateField.errorMessage.value"
              type="date"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-calendar-end"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>
        </v-card-text>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-card-actions class="pa-4 flex-shrink-0 bg-grey-lighten-5">
          <v-btn
            type="button"
            variant="outlined"
            @click="$emit('update:modelValue', false)"
            :disabled="isSubmitting"
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
            :loading="isSubmitting"
            :disabled="isSubmitting"
            class="flex-grow-1"
            size="small"
            prepend-icon="mdi-send"
          >
            Submit Request
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue'
import { useZodForm } from '@/composables'
import { z } from 'zod'
import { useCreateLeaveRequest } from '@/composables'
import { useNotification } from '@/composables'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
  'close': []
}>()

const auth = useAuthStore()
const createMutation = useCreateLeaveRequest()
const { showNotification } = useNotification()
const formRef = ref()

const employeeUuid = computed(() => auth.user?.employee?.uuid)

const leaveTypes = [
  { title: 'Vacation', value: 'vacation' },
  { title: 'Sick Leave', value: 'sick' },
]

const leaveRequestSchema = z.object({
  leave_type: z.enum(['vacation', 'sick'], {
    required_error: 'Leave type is required',
  }),
  start_date: z.string().min(1, 'Start date is required'),
  end_date: z.string().min(1, 'End date is required'),
}).refine((data) => {
  return new Date(data.end_date) >= new Date(data.start_date)
}, {
  message: 'End date must be after or equal to start date',
  path: ['end_date'],
})

const { handleSubmit, createField, isSubmitting, setFieldValue, resetForm } = useZodForm(leaveRequestSchema)

const leaveTypeField = createField('leave_type')
const startDateField = createField('start_date')
const endDateField = createField('end_date')

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    // Reset form and set default values
    resetForm()
    // Use nextTick to ensure form is reset before setting values
    nextTick(() => {
      setFieldValue('leave_type', 'vacation')
      setFieldValue('start_date', '')
      setFieldValue('end_date', '')
    })
  }
})

const onSubmit = handleSubmit(async (values) => {
  if (!employeeUuid.value) {
    showNotification('Employee information not found. Please log in again.', 'error')
    return
  }

  try {
    await createMutation.mutateAsync({
      employee_uuid: employeeUuid.value,
      leave_type: values.leave_type,
      start_date: values.start_date,
      end_date: values.end_date,
    })
    showNotification('Leave request submitted successfully', 'success')
    emit('success')
    emit('update:modelValue', false)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to submit leave request'
    showNotification(message, 'error')
  }
})
</script>
