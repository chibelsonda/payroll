<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Name</div>
        <v-text-field
          v-bind="createField('name')"
          placeholder="Enter deduction name"
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
        <div class="text-body-2 mb-1 font-weight-medium">Type</div>
        <v-select
          v-bind="createField('type')"
          :items="typeOptions"
          placeholder="Select type"
          prepend-inner-icon="mdi-format-list-bulleted-type"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field v-select"
        />
      </div>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
import { watch } from 'vue'
import { useZodForm } from '@/composables'
import { deductionSchema, type DeductionFormData } from '@/validation'
import { useCreateDeduction, useUpdateDeduction } from '@/composables'
import type { Deduction } from '@/types/deduction'
import { useNotification } from '@/composables'

interface Props {
  deduction?: Deduction | null
}

const props = withDefaults(defineProps<Props>(), {
  deduction: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const { showNotification } = useNotification()
const createDeduction = useCreateDeduction()
const updateDeduction = useUpdateDeduction()

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm } = useZodForm(deductionSchema, {
  name: '',
  type: 'fixed' as const,
})

const typeOptions = [
  { title: 'Fixed', value: 'fixed' },
  { title: 'Percentage', value: 'percentage' },
]

// Watch for deduction changes to populate form
watch(() => props.deduction, (deduction) => {
  if (deduction) {
    setFieldValue('name', deduction.name)
    setFieldValue('type', deduction.type as 'fixed' | 'percentage')
  } else {
    resetForm()
  }
}, { immediate: true })

const handleSubmit = async () => {
  await handleFormSubmit(async (values: DeductionFormData) => {
    try {
      if (props.deduction) {
        await updateDeduction.mutateAsync({
          uuid: props.deduction.uuid,
          data: values,
        })
        showNotification('Deduction updated successfully', 'success')
      } else {
        await createDeduction.mutateAsync(values)
        showNotification('Deduction created successfully', 'success')
      }
      emit('submit')
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      const message = err?.response?.data?.message || 'Failed to save deduction'
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
