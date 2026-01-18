<template>
  <v-dialog :model-value="modelValue" max-width="500" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="px-4 py-3">
        <div class="d-flex align-center justify-space-between">
          <span class="text-h6 font-weight-bold">Send Invitation</span>
          <v-btn icon="mdi-close" variant="text" size="small" @click="$emit('update:modelValue', false)">
          </v-btn>
        </div>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <form @submit.prevent="handleSubmit">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="formData.email"
                label="Email Address"
                type="email"
                prepend-inner-icon="mdi-email"
                variant="outlined"
                density="comfortable"
                :error-messages="fieldErrors('email')"
                required
              />
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="formData.role"
                label="Role"
                :items="roleOptions"
                prepend-inner-icon="mdi-account-cog"
                variant="outlined"
                density="comfortable"
                :error-messages="fieldErrors('role')"
                required
              >
                <template v-slot:selection="{ item }">
                  <v-chip
                    :color="getRoleColor(item.value)"
                    size="small"
                    variant="flat"
                  >
                    {{ item.title }}
                  </v-chip>
                </template>
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item
                    v-bind="itemProps"
                    :title="item.title"
                    class="px-3 py-2"
                  >
                    <template v-slot:prepend>
                      <v-chip
                        :color="getRoleColor(item.value)"
                        size="small"
                        variant="flat"
                        class="me-3 text-white"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>
          </v-row>
        </form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="px-4 py-3">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="$emit('update:modelValue', false)">Cancel</v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          :loading="isSubmitting"
          @click="handleSubmit"
        >
          Send Invitation
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useCreateInvitation } from '@/composables'
import { createInvitationSchema, type CreateInvitationFormData } from '@/validation/invitation'
import { useZodForm } from '@/composables'

interface Props {
  modelValue: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'success'): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const createInvitationMutation = useCreateInvitation()

const initialFormData: CreateInvitationFormData = {
  email: '',
  role: 'employee',
}

const formData = ref<CreateInvitationFormData>(initialFormData)

const roleOptions = [
  { title: 'Owner', value: 'owner' },
  { title: 'Admin', value: 'admin' },
  { title: 'HR', value: 'hr' },
  { title: 'Finance', value: 'finance' },
  { title: 'Employee', value: 'employee' },
]

const { handleSubmit: baseHandleSubmit, fieldErrors, setServerErrors, setFieldValue, values } = useZodForm(
  createInvitationSchema,
  initialFormData
)

// Sync formData with VeeValidate values
watch(values, (newValues) => {
  if (newValues) {
    formData.value = { ...newValues } as CreateInvitationFormData
  }
}, { deep: true })

// Sync VeeValidate with formData changes
watch(() => formData.value.email, (email) => {
  setFieldValue('email', email)
})
watch(() => formData.value.role, (role) => {
  setFieldValue('role', role)
})

const isSubmitting = computed(() => createInvitationMutation.isPending.value)

const handleSubmit = baseHandleSubmit(async (values: unknown) => {
  const data = values as CreateInvitationFormData

  try {
    await createInvitationMutation.mutateAsync(data)
    emit('success')
    // Reset form
    formData.value = {
      email: '',
      role: 'employee',
    }
  } catch (error: unknown) {
    const apiError = error as { response?: { data?: { errors?: Record<string, string | string[]> } } }
    if (apiError?.response?.data?.errors) {
      setServerErrors(apiError.response.data.errors)
    }
  }
})

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    owner: 'purple',
    admin: 'blue',
    hr: 'green',
    finance: 'orange',
    employee: 'grey',
  }
  return colors[role] || 'grey'
}
</script>
