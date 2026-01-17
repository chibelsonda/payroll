<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="10" md="6" lg="5" xl="4">
        <v-card class="elevation-2" rounded="lg">
          <v-card-text class="pa-8">
            <div class="text-h4 font-weight-medium mb-2 text-center">Create Your Company</div>
            <div class="text-body-2 text-center text-medium-emphasis mb-6">
              To get started, please create your company profile
            </div>

            <v-form ref="formRef" @submit.prevent="handleSubmit">
              <!-- Company Name -->
              <v-text-field
                v-model="companyNameField.value.value"
                :error-messages="companyNameField.errorMessage.value"
                :error="companyNameField.hasError.value"
                label="Company Name"
                placeholder="Enter your company name"
                prepend-inner-icon="mdi-office-building"
                required
                density="compact"
                variant="outlined"
                hide-details="auto"
                class="mb-4"
              ></v-text-field>

              <!-- Registration Number (Optional) -->
              <v-text-field
                v-model="registrationNoField.value.value"
                :error-messages="registrationNoField.errorMessage.value"
                :error="registrationNoField.hasError.value"
                label="Registration Number"
                placeholder="Enter registration number (optional)"
                prepend-inner-icon="mdi-file-document"
                density="compact"
                variant="outlined"
                hide-details="auto"
                class="mb-4"
              ></v-text-field>

              <!-- Address (Optional) -->
              <v-textarea
                v-model="addressField.value.value"
                :error-messages="addressField.errorMessage.value"
                :error="addressField.hasError.value"
                label="Address"
                placeholder="Enter company address (optional)"
                prepend-inner-icon="mdi-map-marker"
                density="compact"
                variant="outlined"
                rows="3"
                hide-details="auto"
                class="mb-6"
              ></v-textarea>

              <!-- Error Message -->
              <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4" rounded="lg">
                {{ errorMessage }}
              </v-alert>

              <!-- Submit Button -->
              <v-btn
                type="submit"
                color="primary"
                :loading="isSubmitting || createCompanyMutation.isPending.value"
                :disabled="!isValid"
                block
                size="large"
                class="text-uppercase font-weight-bold mb-4"
              >
                Create Company
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useZodForm } from '@/composables'
import { useCreateCompany } from '@/composables'
import { useNotification } from '@/composables'
import { createCompanySchema, type CreateCompanyFormData } from '@/validation'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'
import { useQueryClient } from '@tanstack/vue-query'

const router = useRouter()
const notification = useNotification()
const auth = useAuthStore()
const companyStore = useCompanyStore()
const queryClient = useQueryClient()
const createCompanyMutation = useCreateCompany()
const errorMessage = ref<string | null>(null)

// Initialize form with Zod validation
const {
  handleSubmit: baseHandleSubmit,
  createField,
  isSubmitting,
  isValid,
  setServerErrors,
  clearServerErrors,
} = useZodForm<CreateCompanyFormData>(createCompanySchema, {
  name: '',
  registration_no: null,
  address: null,
})

// Create fields with validation
const companyNameField = createField('name')
const registrationNoField = createField('registration_no')
const addressField = createField('address')

// Handle form submission
const handleSubmit = baseHandleSubmit(async (values: unknown) => {
  errorMessage.value = null
  clearServerErrors()

  const formData = values as CreateCompanyFormData

  try {
    const createdCompany = await createCompanyMutation.mutateAsync({
      name: formData.name,
      registration_no: formData.registration_no || undefined,
      address: formData.address || undefined,
    })

    notification.showSuccess('Company created successfully!')

    // Set the created company as the active company in the store FIRST
    // This ensures the X-Company-ID header is set before fetching user data
    if (createdCompany?.uuid) {
      companyStore.setActiveCompany(createdCompany.uuid)
      // Small delay to ensure localStorage is updated and axios interceptor picks it up
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    // Invalidate and refetch user query to get updated role with company context
    await queryClient.invalidateQueries({ queryKey: ['user'] })
    // Invalidate companies query to refetch and include the new company
    await queryClient.invalidateQueries({ queryKey: ['companies'] })
    // Fetch user data with the new company context and wait for it to complete
    await auth.fetchUser()

    // Wait a bit more to ensure the user data is fully updated
    await new Promise(resolve => setTimeout(resolve, 200))

    // Get fresh user data from the query - ensure we have the latest data
    const userData = queryClient.getQueryData<{ role?: string }>(['user'])
    const user = (userData || auth.user) as { role?: string } | null
    if (user?.role === 'owner') {
      await router.push('/owner')
    } else if (user?.role === 'admin') {
      await router.push('/admin')
    } else {
      await router.push('/employee')
    }
  } catch (error: unknown) {
    const err = error as { response?: { data?: { errors?: Record<string, string | string[]>; message?: string } } }
    if (err?.response?.data?.errors) {
      setServerErrors(err.response.data.errors)
    }

    const status = err?.response?.status
    if (status === 403) {
      await router.push('/verify-email-notice')
      return
    }

    const message = err?.response?.data?.message || 'Failed to create company. Please try again.'
    errorMessage.value = message
    notification.showError(message)
  }
})

onMounted(async () => {
  // Ensure user is loaded; if not verified, redirect to verification notice
  if (!auth.user) {
    await auth.fetchUser()
  }

  if (!auth.user?.email_verified_at) {
    await router.push('/verify-email-notice')
  }
})
</script>
