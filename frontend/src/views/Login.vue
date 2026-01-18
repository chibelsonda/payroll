<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="10" md="5" lg="3" xl="3">
        <v-card class="elevation-2" rounded="lg">
          <v-card-text class="pa-8">

            <!-- Login Title -->
            <div class="text-h4 font-weight-medium mb-2 text-center">Login</div>
            <div class="text-body-2 text-medium-emphasis mb-6 text-center">
              Access your HRIS account to continue.
            </div>

            <!-- <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              class="mb-4"
              density="comfortable"
              rounded="lg"
            >
              {{ errorMessage }}
            </v-alert> -->

            <v-form @submit.prevent="onSubmit">

              <!-- EMAIL -->
              <div class="mb-6">
                <div class="text-body-2 mb-1">Account</div>
                <v-text-field
                  :model-value="emailField.value.value"
                  @update:model-value="emailField.handleChange"
                  @update:focused="(f: boolean) => !f && emailField.handleBlur()"
                  placeholder="Email address"
                  type="email"
                  prepend-inner-icon="mdi-email-outline"
                  :error-messages="emailField.meta.touched ? emailField.errorMessage.value : ''"
                  autocomplete="email"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="mb-2"
                />
              </div>

              <!-- PASSWORD -->
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
                  :model-value="passwordField.value.value"
                  @update:model-value="passwordField.handleChange"
                  @update:focused="(f: boolean) => !f && passwordField.handleBlur()"
                  placeholder="Enter your password"
                  :type="showPassword ? 'text' : 'password'"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                  :error-messages="passwordField.meta.touched ? passwordField.errorMessage.value : ''"
                  autocomplete="current-password"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  @click:append-inner="showPassword = !showPassword"
                />
              </div>

              <!-- LOGIN BUTTON -->
              <v-btn
                type="submit"
                color="primary"
                :loading="auth.isLoginLoading || isSubmitting"
                block
                class="text-uppercase font-weight-bold mb-4 mt-5"
              >
                LOG IN
              </v-btn>

            </v-form>

            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable" rounded="lg">
              <div class="d-flex align-start">
                <v-icon class="me-2 mt-1" color="primary">mdi-email-check-outline</v-icon>
                <div>
                  <div class="font-weight-medium mb-1">Haven't verified yet?</div>
                  <div class="text-body-2 text-medium-emphasis">
                    Check your inbox for the verification link or go to the verification page.
                  </div>
                  <router-link to="/verify-email-notice" class="text-primary text-decoration-none text-body-2">
                    Go to verification
                  </router-link>
                </div>
              </div>
            </v-alert>

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
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotification } from '@/composables'

import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { loginSchema, type LoginFormData } from '@/validation'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const notification = useNotification()

const showPassword = ref(false)
const errorMessage = ref<string | null>(null)

/* ---------------- FORM ---------------- */
const { handleSubmit, isSubmitting, setErrors } = useForm<LoginFormData>({
  validationSchema: toTypedSchema(loginSchema),
})

/* ---------------- FIELDS ---------------- */
const emailField = useField<string>('email')
const passwordField = useField<string>('password')

/* ---------------- SUBMIT ---------------- */
const onSubmit = handleSubmit(async (values) => {
  errorMessage.value = null

  try {
    const user = await auth.login(values)
    notification.showSuccess('Login successful!')

    const redirect = route.query.redirect as string | undefined
    const token = route.query.token as string | undefined

    if (redirect && token) {
      await router.push(`${redirect}?token=${token}`)
      return
    }

    if (!user.email_verified_at) {
      await router.push('/verify-email-notice')
      return
    }

    if (!user.has_company) {
      await router.push('/onboarding/create-company')
      return
    }

    if (user.role === 'owner') {
      await router.push('/owner')
    } else if (user.role === 'admin') {
      await router.push('/admin')
    } else {
      await router.push('/employee')
    }
  } catch (error: unknown) {
    const err = error as {
      response?: {
        data?: {
          errors?: Record<string, string | string[]>
          message?: string
        }
      }
    }

    if (err?.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }

    const message =
      err?.response?.data?.message || 'Login failed. Please check your credentials.'

    errorMessage.value = message
    notification.showError(message)
  }
})
</script>
