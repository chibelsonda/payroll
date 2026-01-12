<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="10" md="5" lg="3" xl="3">
        <v-card class="elevation-2" rounded="lg">
          <v-card-text class="pa-8">
            <!-- Login Title -->
            <div class="text-h4 font-weight-medium mb-6 text-center">Login</div>

            <v-form @submit="onSubmit">
              <!-- Account Section -->
              <div class="mb-6">
                <div class="text-body-2 mb-1">Account</div>
                <v-text-field
                  v-model="email"
                  name="email"
                  placeholder="Email address"
                  type="email"
                  prepend-inner-icon="mdi-email-outline"
                  :error-messages="emailError"
                  :error="hasEmailError"
                  required
                  autocomplete="email"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="mb-2"
                ></v-text-field>
              </div>

              <!-- Password Section -->
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-1">
                  <div class="text-body-2">Password</div>
                  <router-link
                    to="#"
                    @click.prevent
                    class="text-primary text-decoration-none text-body-2"
                  >
                    Forgot login password?
                  </router-link>
                </div>
                <v-text-field
                  v-model="password"
                  name="password"
                  placeholder="Enter your password"
                  :type="showPassword ? 'text' : 'password'"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                  :error-messages="passwordError"
                  :error="hasPasswordError"
                  required
                  autocomplete="current-password"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  @click:append-inner="showPassword = !showPassword"
                ></v-text-field>
              </div>

              <!-- Warning Message -->
              <v-alert
                type="warning"
                variant="tonal"
                density="compact"
                class="mb-6 text-body-2"
              >
                <strong>Warning:</strong> After 3 consecutive failed login attempts, your account
                will be temporarily locked for three hours. If you must login now, you can also
                click "Forgot login password?" below to reset the login password.
              </v-alert>

              <!-- Login Button -->
              <v-btn
                type="submit"
                color="primary"
                :loading="auth.isLoginLoading || isSubmitting"
                :disabled="!isValid"
                block
                size="large"
                class="text-uppercase font-weight-bold mb-4"
              >
                LOG IN
              </v-btn>
            </v-form>

            <!-- Sign Up Link -->
            <div class="text-center">
              <router-link
                to="/register"
                class="text-primary text-decoration-none text-body-1 font-weight-medium"
              >
                Sign up now
                <v-icon size="small" class="ml-1">mdi-chevron-right</v-icon>
              </router-link>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useNotification } from '@/composables'
import { useZodForm } from '@/composables'
import { loginSchema, type LoginFormData } from '@/validation'

const auth = useAuthStore()
const router = useRouter()
const notification = useNotification()
const showPassword = ref(false)
const errorMessage = ref<string | null>(null)

// Initialize form with Zod validation
const {
  handleSubmit,
  createField,
  isSubmitting,
  isValid,
  setServerErrors,
  clearServerErrors,
} = useZodForm<LoginFormData>(loginSchema, {
  email: '',
  password: '',
})

// Create fields with validation
const emailField = createField('email')
const passwordField = createField('password')

// Bind field values
const email = computed({
  get: () => emailField.value.value as string,
  set: (value: string) => emailField.setValue(value),
})

const password = computed({
  get: () => passwordField.value.value as string,
  set: (value: string) => passwordField.setValue(value),
})

// Error messages
const emailError = computed(() => emailField.errorMessage.value)
const passwordError = computed(() => passwordField.errorMessage.value)
const hasEmailError = computed(() => !!emailError.value)
const hasPasswordError = computed(() => !!passwordError.value)

// Handle form submission
const onSubmit = handleSubmit(async (values: unknown) => {
  errorMessage.value = null
  clearServerErrors()

  const formData = values as LoginFormData

  try {
    const user = await auth.login(formData)
    notification.showSuccess('Login successful!')

    // Check if user has a company
    if (!user.has_company) {
      // User doesn't have a company - redirect to onboarding
      await router.push('/onboarding/create-company')
      return
    }

    // User has a company - redirect based on role
    if (user.role === 'owner' || user.role === 'admin') {
      await router.push('/admin')
    } else if (user.role === 'employee' || user.role === 'hr' || user.role === 'payroll') {
      await router.push('/employee')
    } else {
      // Fallback: redirect to onboarding if role is not determined
      await router.push('/onboarding/create-company')
    }
  } catch (error: unknown) {
    // Handle server-side validation errors
    const err = error as { response?: { data?: { errors?: Record<string, string | string[]>; message?: string } } }
    if (err?.response?.data?.errors) {
      setServerErrors(err.response.data.errors)
    }

    const message =
      err?.response?.data?.message || 'Login failed. Please check your credentials.'
    errorMessage.value = message
    notification.showError(message)
  }
})
</script>
