<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="10" md="5" lg="3" xl="3">
        <v-card class="elevation-2" rounded="lg">
          <v-card-text class="pa-8">

            <div class="text-h4 font-weight-medium mb-6 text-center">Sign Up</div>

            <v-form @submit="onSubmit">
              <!-- Personal Information Section -->
              <div class="mb-6">
                <div class="text-body-2 mb-1">Personal Information</div>
                <v-text-field
                  v-model="firstName"
                  name="first_name"
                  placeholder="First name"
                  prepend-inner-icon="mdi-account-outline"
                  :error-messages="firstNameError"
                  :error="hasFirstNameError"
                  required
                  autocomplete="given-name"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="mb-2"
                ></v-text-field>
                <v-text-field
                  v-model="lastName"
                  name="last_name"
                  placeholder="Last name"
                  prepend-inner-icon="mdi-account-outline"
                  :error-messages="lastNameError"
                  :error="hasLastNameError"
                  required
                  autocomplete="family-name"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                ></v-text-field>
              </div>

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
                <div class="text-body-2 mb-1">Password</div>
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
                  autocomplete="new-password"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  hint="Must contain uppercase, lowercase, number, and special character"
                  persistent-hint
                  class="mb-2"
                  @click:append-inner="showPassword = !showPassword"
                ></v-text-field>
                <v-text-field
                  v-model="passwordConfirmation"
                  name="password_confirmation"
                  placeholder="Confirm your password"
                  :type="showPasswordConfirm ? 'text' : 'password'"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :append-inner-icon="showPasswordConfirm ? 'mdi-eye' : 'mdi-eye-off'"
                  :error-messages="passwordConfirmationError"
                  :error="hasPasswordConfirmationError"
                  required
                  autocomplete="new-password"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                ></v-text-field>
              </div>

              <!-- Register Button -->
              <v-btn
                type="submit"
                color="primary"
                :loading="auth.isRegisterLoading || isSubmitting"
                :disabled="!isValid"
                block
                class="text-uppercase font-weight-bold mb-4"
              >
                SIGN UP
              </v-btn>
            </v-form>

            <!-- Login Link -->
            <div class="text-center">
              <router-link
                to="/login"
                class="text-primary text-decoration-none text-body-1 font-weight-medium"
              >
                Already have an account? Login
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
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { useNotification } from '@/composables'
import { useZodForm } from '@/composables'
import { registerSchema, type RegisterFormData } from '@/validation'
import { webAxios } from '@/lib/axios'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const notification = useNotification()
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const errorMessage = ref<string | null>(null)

// Initialize form with Zod validation
const {
  handleSubmit,
  createField,
  isSubmitting,
  isValid,
  setServerErrors,
  clearServerErrors,
} = useZodForm<RegisterFormData>(registerSchema, {
  first_name: 'try',
  last_name: 'inv',
  email: '',
  password: 'Pass!234',
  password_confirmation: 'Pass!234',
  role: 'employee',
})

// Create fields with validation
const firstNameField = createField('first_name')
const lastNameField = createField('last_name')
const emailField = createField('email')
const passwordField = createField('password')
const passwordConfirmationField = createField('password_confirmation')

// Bind field values
const firstName = computed({
  get: () => firstNameField.value.value as string,
  set: (value: string) => firstNameField.setValue(value),
})

const lastName = computed({
  get: () => lastNameField.value.value as string,
  set: (value: string) => lastNameField.setValue(value),
})

const email = computed({
  get: () => emailField.value.value as string,
  set: (value: string) => emailField.setValue(value),
})

// Pre-fill email from query parameter if coming from invitation
onMounted(() => {
  const emailFromQuery = route.query.email as string | undefined
  if (emailFromQuery && !email.value) {
    emailField.setValue(emailFromQuery)
  }
})

const password = computed({
  get: () => passwordField.value.value as string,
  set: (value: string) => passwordField.setValue(value),
})

const passwordConfirmation = computed({
  get: () => passwordConfirmationField.value.value as string,
  set: (value: string) => passwordConfirmationField.setValue(value),
})

// Error messages
const firstNameError = computed(() => firstNameField.errorMessage.value)
const lastNameError = computed(() => lastNameField.errorMessage.value)
const emailError = computed(() => emailField.errorMessage.value)
const passwordError = computed(() => passwordField.errorMessage.value)
const passwordConfirmationError = computed(() => passwordConfirmationField.errorMessage.value)

// Error states
const hasFirstNameError = computed(() => !!firstNameError.value)
const hasLastNameError = computed(() => !!lastNameError.value)
const hasEmailError = computed(() => !!emailError.value)
const hasPasswordError = computed(() => !!passwordError.value)
const hasPasswordConfirmationError = computed(() => !!passwordConfirmationError.value)

// Clear error message
const clearError = () => {
  errorMessage.value = null
  clearServerErrors()
}

// Handle form submission
const onSubmit = handleSubmit(async (values: unknown) => {
  errorMessage.value = null
  clearServerErrors()

  const formData = values as RegisterFormData

  try {
    await auth.register(formData)
    notification.showSuccess('Registration successful. Please verify your email.')

    // CRITICAL: Ensure session is properly established
    // Fetch CSRF cookie to ensure session cookie is set
    try {
      await webAxios.get('/sanctum/csrf-cookie')
      await new Promise(resolve => setTimeout(resolve, 200))
    } catch {
      // If CSRF cookie fetch fails, continue anyway
    }

    // Ensure session is established and user data is loaded before redirecting.
    // If it fails, fall back to login with redirect.
    const hasUser = await auth.fetchUser()
    if (!hasUser) {
      await router.push({ name: 'login', query: { redirect: '/verify-email-notice' } })
      return
    }

    // Check if there's a redirect query parameter (e.g., from invitation link)
    const redirect = route.query.redirect as string | undefined
    const token = route.query.token as string | undefined

    if (redirect && token) {
      // Redirect to the specified path with token (e.g., accept invitation)
      await router.push(`${redirect}?token=${token}`)
      return
    }

    // After registration, prompt user to verify email before company setup
    await router.push('/verify-email-notice')
  } catch (error: unknown) {
    // Handle server-side validation errors
    const err = error as { response?: { data?: { errors?: Record<string, string | string[]>; message?: string } } }
    if (err?.response?.data?.errors) {
      setServerErrors(err.response.data.errors)
    }

    const message =
      err?.response?.data?.message || 'Registration failed. Please try again.'
    errorMessage.value = message
    notification.showError(message)
  }
})
</script>
