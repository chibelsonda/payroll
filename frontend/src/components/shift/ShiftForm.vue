<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Name</div>
        <v-text-field
          v-bind="createField('name')"
          placeholder="Enter shift name (e.g., Morning Shift, Night Shift)"
          prepend-inner-icon="mdi-clock-outline"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Start Time</div>
        <v-text-field
          v-bind="createField('start_time')"
          type="time"
          placeholder="Select start time"
          prepend-inner-icon="mdi-clock-start"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">End Time</div>
        <v-text-field
          v-bind="createField('end_time')"
          type="time"
          placeholder="Select end time"
          prepend-inner-icon="mdi-clock-end"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Break Duration (minutes)</div>
        <v-text-field
          v-bind="createField('break_duration_minutes')"
          type="number"
          placeholder="Enter break duration"
          prepend-inner-icon="mdi-timer-outline"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <v-checkbox
          v-bind="createField('is_active')"
          label="Active"
          hide-details="auto"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Description</div>
        <v-textarea
          v-bind="createField('description')"
          placeholder="Enter description (optional)"
          variant="outlined"
          density="compact"
          hide-details="auto"
          rows="3"
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
import type { Shift } from '@/types/shift'

interface Props {
  shift?: Shift | null
}

const props = withDefaults(defineProps<Props>(), {
  shift: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const shiftSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  start_time: z.string().min(1, 'Start time is required'),
  end_time: z.string().min(1, 'End time is required'),
  break_duration_minutes: z.string().optional(),
  is_active: z.boolean().optional(),
  description: z.string().optional(),
})

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm } = useZodForm(
  shiftSchema,
  {
    name: props.shift?.name || '',
    start_time: props.shift?.start_time || '',
    end_time: props.shift?.end_time || '',
    break_duration_minutes: props.shift?.break_duration_minutes?.toString() || '60',
    is_active: props.shift?.is_active ?? true,
    description: props.shift?.description || '',
  }
)

watch(() => props.shift, (shift) => {
  if (shift) {
    setFieldValue('name', shift.name || '')
    setFieldValue('start_time', shift.start_time || '')
    setFieldValue('end_time', shift.end_time || '')
    setFieldValue('break_duration_minutes', shift.break_duration_minutes?.toString() || '60')
    setFieldValue('is_active', shift.is_active ?? true)
    setFieldValue('description', shift.description || '')
  } else {
    resetForm()
  }
})

defineExpose({
  handleSubmit: handleFormSubmit,
  isSubmitting,
  isValid,
  values: computed(() => ({
    name: createField('name').modelValue.value,
    start_time: createField('start_time').modelValue.value,
    end_time: createField('end_time').modelValue.value,
    break_duration_minutes: parseInt(createField('break_duration_minutes').modelValue.value || '60'),
    is_active: createField('is_active').modelValue.value ?? true,
    description: createField('description').modelValue.value || null,
  })),
})
</script>
