<template>
  <form @submit.prevent="handleSubmit">
    <v-text-field
      v-bind="createField('name')"
      label="Name"
      prepend-inner-icon="mdi-text"
      variant="outlined"
      density="compact"
    />

    <v-select
      v-bind="createField('type')"
      :items="typeOptions"
      label="Type"
      prepend-inner-icon="mdi-format-list-bulleted-type"
      variant="outlined"
      density="compact"
    />

    <div class="d-flex justify-end gap-2 mt-4">
      <v-btn variant="text" @click="$emit('cancel')"> Cancel </v-btn>
      <v-btn
        type="submit"
        color="primary"
        :loading="isSubmitting"
        :disabled="!isValid"
      >
        {{ deduction ? 'Update' : 'Create' }}
      </v-btn>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useZodForm } from '@/composables'
import { deductionSchema, type DeductionFormData } from '@/validation'
import { useCreateDeduction, useUpdateDeduction } from '@/composables'
import type { Deduction } from '@/types/deduction'
import { useNotification } from '@/composables/useNotification'

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

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid } = useZodForm({
  schema: deductionSchema,
  initialValues: computed(() => ({
    name: props.deduction?.name || '',
    type: (props.deduction?.type as 'fixed' | 'percentage') || 'fixed',
  })),
})

const typeOptions = [
  { title: 'Fixed', value: 'fixed' },
  { title: 'Percentage', value: 'percentage' },
]

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
    } catch (error: any) {
      const message = error.response?.data?.message || 'Failed to save deduction'
      showNotification(message, 'error')
      throw error
    }
  })
}
</script>
