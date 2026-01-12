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
            <v-select
              v-model="employeeField.value.value"
              :error-messages="employeeField.errorMessage.value"
              :items="employees"
              item-value="uuid"
              item-title="display_name"
              placeholder="Select employee"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-account"
              hide-details="auto"
              class="employee-form-field v-select"
            ></v-select>
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
import { useCreateAttendance, useUpdateAttendance, useAttendance } from '@/composables/useAttendance'
import { useEmployees } from '@/composables/useEmployees'
import { useNotification } from '@/composables/useNotification'

const props = defineProps<{
  modelValue: boolean
  attendanceUuid?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
  'close': []
}>()

const { showNotification } = useNotification()
const createMutation = useCreateAttendance()
const updateMutation = useUpdateAttendance()
const { data: employeesData } = useEmployees()

const employees = computed(() => {
  if (!employeesData.value?.data) return []
  return employeesData.value.data.map((emp) => ({
    uuid: emp.uuid,
    display_name: `${emp.user?.first_name} ${emp.user?.last_name} (${emp.employee_no})`,
  }))
})

const isEdit = computed(() => !!props.attendanceUuid)
const formRef = ref()

const { data: attendanceData } = useAttendance(computed(() => props.attendanceUuid))

const attendanceSchema = z.object({
  employee_uuid: z.string().min(1, 'Employee is required'),
  date: z.string().min(1, 'Date is required'),
  time_in: z.string().optional(),
  time_out: z.string().optional(),
  hours_worked: z.string().optional(),
})

const { handleSubmit, createField, isSubmitting, setFieldValue } = useZodForm(attendanceSchema, {
  employee_uuid: '',
  date: new Date().toISOString().split('T')[0],
  time_in: '',
  time_out: '',
  hours_worked: '',
})

const employeeField = createField('employee_uuid')
const dateField = createField('date')
const timeInField = createField('time_in')
const timeOutField = createField('time_out')
const hoursWorkedField = createField('hours_worked')

watch(() => props.modelValue, (newVal) => {
  if (newVal && props.attendanceUuid && attendanceData.value) {
    // Load attendance data for editing
    const attendance = attendanceData.value
    setFieldValue('employee_uuid', attendance.employee?.uuid || attendance.employee_uuid || '')
    setFieldValue('date', attendance.date || new Date().toISOString().split('T')[0])
    setFieldValue('time_in', attendance.time_in || '')
    setFieldValue('time_out', attendance.time_out || '')
    setFieldValue('hours_worked', attendance.hours_worked || '')
  } else if (newVal) {
    // Reset form for new attendance
    setFieldValue('employee_uuid', '')
    setFieldValue('date', new Date().toISOString().split('T')[0])
    setFieldValue('time_in', '')
    setFieldValue('time_out', '')
    setFieldValue('hours_worked', '')
  }
})

watch(attendanceData, (attendance) => {
  if (attendance && props.attendanceUuid && props.modelValue) {
    // Load attendance data when it becomes available
    setFieldValue('employee_uuid', attendance.employee?.uuid || attendance.employee_uuid || '')
    setFieldValue('date', attendance.date || new Date().toISOString().split('T')[0])
    setFieldValue('time_in', attendance.time_in || '')
    setFieldValue('time_out', attendance.time_out || '')
    setFieldValue('hours_worked', attendance.hours_worked || '')
  }
})

const onSubmit = handleSubmit(async (values) => {
  try {
    const payload = {
      employee_uuid: values.employee_uuid,
      date: values.date,
      time_in: values.time_in || undefined,
      time_out: values.time_out || undefined,
      hours_worked: values.hours_worked ? parseFloat(values.hours_worked) : undefined,
    }

    if (isEdit.value && props.attendanceUuid) {
      await updateMutation.mutateAsync({
        uuid: props.attendanceUuid,
        data: payload,
      })
      showNotification('Attendance record updated successfully', 'success')
    } else {
      await createMutation.mutateAsync(payload)
      showNotification('Attendance record created successfully', 'success')
    }
    emit('success')
    emit('update:modelValue', false)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to save attendance record'
    showNotification(message, 'error')
  }
})
</script>
