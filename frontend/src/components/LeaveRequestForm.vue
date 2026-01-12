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
        <v-card-text class="flex-grow-1 overflow-y-auto pa-5" style="min-height: 0;">
          <v-row>
            <v-col cols="12">
              <v-select
                v-model="leaveTypeField.value.value"
                :error-messages="leaveTypeField.errorMessage.value"
                :items="leaveTypes"
                label="Leave Type"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-calendar-question"
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="startDateField.value.value"
                :error-messages="startDateField.errorMessage.value"
                label="Start Date"
                type="date"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-calendar-start"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="endDateField.value.value"
                :error-messages="endDateField.errorMessage.value"
                label="End Date"
                type="date"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-calendar-end"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-card-actions class="flex-shrink-0 px-5 py-3">
          <v-spacer></v-spacer>
          <v-btn
            variant="text"
            @click="$emit('update:modelValue', false)"
            :disabled="isSubmitting"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            type="submit"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          >
            Submit Request
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { z } from 'zod'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
  'close': []
}>()

const isSubmitting = ref(false)
const formRef = ref()

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

const { handleSubmit, createField } = useZodForm(leaveRequestSchema)

const leaveTypeField = createField('leave_type')
const startDateField = createField('start_date')
const endDateField = createField('end_date')

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    // Reset form
    leaveTypeField.value.value = 'vacation'
    startDateField.value.value = ''
    endDateField.value.value = ''
  }
})

const onSubmit = handleSubmit(async (values) => {
  isSubmitting.value = true
  try {
    // TODO: Implement API call
    console.log('Submit leave request:', values)
    emit('success')
  } catch (error) {
    console.error('Error submitting leave request:', error)
  } finally {
    isSubmitting.value = false
  }
})
</script>
