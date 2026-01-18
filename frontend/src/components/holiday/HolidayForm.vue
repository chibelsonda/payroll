<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Name</div>
        <v-text-field
          v-bind="createField('name')"
          placeholder="Enter holiday name"
          prepend-inner-icon="mdi-calendar-star"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12" md="6">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Date</div>
        <v-text-field
          v-bind="createField('date')"
          type="date"
          placeholder="Select date"
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
        <div class="text-body-2 mb-1 font-weight-medium">Type</div>
        <v-select
          v-bind="createField('type')"
          :items="typeOptions"
          placeholder="Select type"
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
        <v-checkbox
          v-bind="createField('is_recurring')"
          label="Recurring Holiday"
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
import { watch } from 'vue'
import { useZodForm } from '@/composables'
import { z } from 'zod'
import type { Holiday } from '@/types/holiday'

interface Props {
  holiday?: Holiday | null
}

const props = withDefaults(defineProps<Props>(), {
  holiday: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const holidaySchema = z.object({
  name: z.string().min(1, 'Name is required'),
  date: z.string().min(1, 'Date is required'),
  type: z.enum(['regular', 'special', 'local']),
  is_recurring: z.boolean().optional(),
  description: z.string().optional(),
})

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm } = useZodForm(
  holidaySchema,
  {
    name: props.holiday?.name || '',
    date: props.holiday?.date || '',
    type: props.holiday?.type || 'regular',
    is_recurring: props.holiday?.is_recurring || false,
    description: props.holiday?.description || '',
  }
)

const typeOptions = [
  { title: 'Regular', value: 'regular' },
  { title: 'Special', value: 'special' },
  { title: 'Local', value: 'local' },
]

watch(() => props.holiday, (holiday) => {
  if (holiday) {
    setFieldValue('name', holiday.name || '')
    setFieldValue('date', holiday.date || '')
    setFieldValue('type', holiday.type || 'regular')
    setFieldValue('is_recurring', holiday.is_recurring || false)
    setFieldValue('description', holiday.description || '')
  } else {
    resetForm()
  }
})

const values = computed(() => ({
  name: createField('name').modelValue.value,
  date: createField('date').modelValue.value,
  type: createField('type').modelValue.value,
  is_recurring: createField('is_recurring').modelValue.value || false,
  description: createField('description').modelValue.value || null,
}))

defineExpose({
  handleSubmit: handleFormSubmit,
  isSubmitting,
  isValid,
  values,
  resetForm,
})
</script>
