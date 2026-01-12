<template>
  <v-row>
    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Name</div>
        <v-text-field
          v-bind="createField('name')"
          placeholder="Enter contribution name"
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
        <div class="text-body-2 mb-1 font-weight-medium">Employee Share (%)</div>
        <v-text-field
          v-bind="createField('employee_share')"
          type="number"
          step="0.01"
          placeholder="Enter employee share percentage"
          prepend-inner-icon="mdi-percent"
          variant="outlined"
          density="compact"
          hide-details="auto"
          class="employee-form-field"
        />
      </div>
    </v-col>

    <v-col cols="12">
      <div class="mb-4">
        <div class="text-body-2 mb-1 font-weight-medium">Employer Share (%)</div>
        <v-text-field
          v-bind="createField('employer_share')"
          type="number"
          step="0.01"
          placeholder="Enter employer share percentage"
          prepend-inner-icon="mdi-percent"
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
import { watch } from 'vue'
import { useZodForm } from '@/composables/useZodForm'
import { contributionSchema, type ContributionFormData } from '@/validation'
import { useCreateContribution, useUpdateContribution } from '@/composables/useContributions'
import type { Contribution } from '@/types/contribution'
import { useNotification } from '@/composables/useNotification'

interface Props {
  contribution?: Contribution | null
}

const props = withDefaults(defineProps<Props>(), {
  contribution: null,
})

const emit = defineEmits<{
  submit: []
  cancel: []
}>()

const { showNotification } = useNotification()
const createContribution = useCreateContribution()
const updateContribution = useUpdateContribution()

const { createField, handleSubmit: handleFormSubmit, isSubmitting, isValid, setFieldValue, resetForm } = useZodForm(contributionSchema, {
  name: '',
  employee_share: '',
  employer_share: '',
})

// Watch for contribution changes to populate form
watch(() => props.contribution, (contribution) => {
  if (contribution) {
    setFieldValue('name', contribution.name)
    setFieldValue('employee_share', contribution.employee_share)
    setFieldValue('employer_share', contribution.employer_share)
  } else {
    resetForm()
  }
}, { immediate: true })

const handleSubmit = async () => {
  await handleFormSubmit(async (values: ContributionFormData) => {
    try {
      if (props.contribution) {
        await updateContribution.mutateAsync({
          uuid: props.contribution.uuid,
          data: {
            name: values.name,
            employee_share: parseFloat(values.employee_share),
            employer_share: parseFloat(values.employer_share),
          },
        })
        showNotification('Contribution updated successfully', 'success')
      } else {
        await createContribution.mutateAsync({
          name: values.name,
          employee_share: parseFloat(values.employee_share),
          employer_share: parseFloat(values.employer_share),
        })
        showNotification('Contribution created successfully', 'success')
      }
      emit('submit')
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      const message = err?.response?.data?.message || 'Failed to save contribution'
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
  resetForm,
})
</script>
