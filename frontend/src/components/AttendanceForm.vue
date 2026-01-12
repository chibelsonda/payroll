<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="500"
    class="drawer attendance-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar color="primary" size="40" class="me-3">
            <v-icon color="white">mdi-calendar-clock</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">{{ isEdit ? 'Edit Attendance' : 'Record Attendance' }}</div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ isEdit ? 'Update attendance record' : 'Record employee attendance for the day' }}
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
          <!-- Employee -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Employee</div>
            <v-text-field
              v-model="employeeField.value.value"
              :error-messages="employeeField.errorMessage.value"
              placeholder="Select employee"
              variant="outlined"
              density="compact"
              readonly
              prepend-inner-icon="mdi-account"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Date -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Date</div>
            <v-text-field
              v-model="dateField.value.value"
              :error-messages="dateField.errorMessage.value"
              type="date"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-calendar"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Time In -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Time In</div>
            <v-text-field
              v-model="timeInField.value.value"
              :error-messages="timeInField.errorMessage.value"
              type="time"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-clock-in"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Time Out -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Time Out</div>
            <v-text-field
              v-model="timeOutField.value.value"
              :error-messages="timeOutField.errorMessage.value"
              type="time"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-clock-out"
              hide-details="auto"
              class="employee-form-field"
            ></v-text-field>
          </div>

          <!-- Hours Worked -->
          <div class="mb-4">
            <div class="text-body-2 mb-1 font-weight-medium">Hours Worked</div>
            <v-text-field
              v-model="hoursWorkedField.value.value"
              :error-messages="hoursWorkedField.errorMessage.value"
              type="number"
              step="0.01"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-timer"
              suffix="hrs"
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
            :prepend-icon="isEdit ? 'mdi-content-save' : 'mdi-check'"
          >
            {{ isEdit ? 'Update' : 'Save' }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { z } from 'zod'

const props = defineProps<{
  modelValue: boolean
  attendanceUuid?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
  'close': []
}>()

const isEdit = computed(() => !!props.attendanceUuid)
const isSubmitting = ref(false)
const formRef = ref()

const attendanceSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  date: z.string().min(1, 'Date is required'),
  time_in: z.string().optional(),
  time_out: z.string().optional(),
  hours_worked: z.string().optional(),
})

const { handleSubmit, createField } = useZodForm(attendanceSchema)

const employeeField = createField('employee_uuid')
const dateField = createField('date')
const timeInField = createField('time_in')
const timeOutField = createField('time_out')
const hoursWorkedField = createField('hours_worked')

watch(() => props.modelValue, (newVal) => {
  if (newVal && props.attendanceUuid) {
    // TODO: Load attendance data for editing
  } else if (newVal) {
    // Reset form for new attendance
    employeeField.value.value = ''
    dateField.value.value = new Date().toISOString().split('T')[0]
    timeInField.value.value = ''
    timeOutField.value.value = ''
    hoursWorkedField.value.value = ''
  }
})

const onSubmit = handleSubmit(async (values) => {
  isSubmitting.value = true
  try {
    // TODO: Implement API call
    console.log('Submit attendance:', values)
    emit('success')
  } catch (error) {
    console.error('Error submitting attendance:', error)
  } finally {
    isSubmitting.value = false
  }
})
</script>
