<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="4" rounded="lg">
          <!-- Loading State -->
          <div v-if="isLoading" class="text-center pa-8">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <p class="mt-4 text-body-1">Loading invitation...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="pa-6">
            <div class="text-center mb-4">
              <v-icon size="64" color="error">mdi-alert-circle</v-icon>
            </div>
            <h2 class="text-h5 text-center mb-4">Invitation Error</h2>
            <v-alert type="error" variant="tonal" class="mb-4">
              {{ error }}
            </v-alert>
            <div class="text-center">
              <v-btn color="primary" variant="flat" to="/login">
                Go to Login
              </v-btn>
            </div>
          </div>

          <!-- Success State -->
          <div v-else-if="isSuccess" class="pa-6">
            <div class="text-center mb-4">
              <v-icon size="64" color="success">mdi-check-circle</v-icon>
            </div>
            <h2 class="text-h5 text-center mb-2">Invitation Accepted!</h2>
            <p class="text-body-1 text-center text-medium-emphasis mb-6">
              You have successfully joined <strong>{{ invitation?.company?.name }}</strong>.
            </p>
            <div class="text-center">
              <v-btn color="primary" variant="flat" @click="handleRedirect">
                Go to Dashboard
              </v-btn>
              <!-- TEMPORARY DEBUG BUTTON -->
              <v-btn
                color="secondary"
                variant="outlined"
                size="small"
                class="mt-2"
                @click="debugSession"
              >
                Debug Session
              </v-btn>
            </div>
          </div>

          <!-- Invitation Details -->
          <div v-else class="pa-6">
            <div class="text-center mb-6">
              <v-icon size="64" color="primary">mdi-email-check</v-icon>
            </div>
            <h2 class="text-h5 text-center mb-2">You're Invited!</h2>
            <p class="text-body-1 text-center text-medium-emphasis mb-6">
              You've been invited to join a company
            </p>

            <v-card variant="outlined" class="mb-6">
              <v-card-text>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis mb-1">Company</div>
                  <div class="text-body-1 font-weight-medium">
                    {{ invitation?.company?.name || 'Loading...' }}
                  </div>
                </div>
                <v-divider class="my-3"></v-divider>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis mb-1">Role</div>
                  <v-chip
                    :color="getRoleColor(invitation?.role)"
                    size="small"
                    variant="flat"
                    class="text-capitalize"
                  >
                    {{ invitation?.role }}
                  </v-chip>
                </div>
                <v-divider class="my-3"></v-divider>
                <div>
                  <div class="text-caption text-medium-emphasis mb-1">Invited by</div>
                  <div class="text-body-1">
                    {{ invitation?.inviter?.name || invitation?.inviter?.email || 'N/A' }}
                  </div>
                </div>
              </v-card-text>
            </v-card>

            <v-alert
              v-if="invitation?.expires_at"
              type="info"
              variant="tonal"
              density="compact"
              class="mb-6"
            >
              <div class="text-caption">
                <strong>Expires:</strong> {{ formatDate(invitation.expires_at) }}
              </div>
            </v-alert>

            <!-- Not Authenticated -->
            <div v-if="!isAuthenticated" class="text-center">
              <p class="text-body-2 text-medium-emphasis mb-2">
                To accept this invitation, you need an account with email:
              </p>
              <p class="text-body-1 font-weight-medium mb-4 text-primary">
                {{ invitation?.email }}
              </p>
              <v-alert
                type="info"
                variant="tonal"
                density="compact"
                class="mb-4 text-left"
              >
                <div class="text-caption">
                  <strong>Don't have an account?</strong> You'll need to register first with the email address above, then come back to accept this invitation.
                </div>
              </v-alert>
              <div class="d-flex flex-column ga-2">
                <v-btn
                  color="primary"
                  variant="flat"
                  block
                  :to="loginUrl"
                >
                  Log In
                </v-btn>
                <v-btn
                  variant="outlined"
                  block
                  :to="registerUrl"
                >
                  Create Account
                </v-btn>
              </div>
            </div>

            <!-- Authenticated -->
            <div v-else class="text-center">
              <!-- Email Mismatch Warning -->
              <v-alert
                v-if="invitation?.email && authStore.user?.email && invitation.email !== authStore.user.email"
                type="warning"
                variant="tonal"
                density="compact"
                class="mb-4 text-left"
              >
                <div class="text-caption">
                  <strong>Email Mismatch:</strong> You're logged in as <strong>{{ authStore.user.email }}</strong>,
                  but this invitation is for <strong>{{ invitation.email }}</strong>.
                  Please log out and log in with the correct email address.
                </div>
              </v-alert>

              <v-btn
                color="primary"
                variant="flat"
                block
                size="large"
                :loading="isAccepting"
                :disabled="!!(invitation?.email && authStore.user?.email && invitation.email !== authStore.user.email)"
                @click="handleAccept"
              >
                Accept Invitation
              </v-btn>
              <v-btn
                variant="text"
                block
                class="mt-2"
                to="/"
              >
                Cancel
              </v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'
import { useAcceptInvitation } from '@/composables'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'
import { formatDateTimeForDisplay } from '@/lib/datetime'
import apiAxios, { webAxios } from '@/lib/axios'
import type { Invitation } from '@/types/invitation'
import type { User } from '@/types/user'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const authStore = useAuthStore()
const companyStore = useCompanyStore()
const acceptInvitationMutation = useAcceptInvitation()

const token = computed(() => route.query.token as string | undefined)
const isAuthenticated = computed(() => authStore.isAuthenticated)
const isLoading = ref(false)
const isAccepting = ref(false)
const error = ref<string | null>(null)
const isSuccess = ref(false)
const invitation = ref<Invitation | null>(null)

const loginUrl = computed(() => {
  if (token.value) {
    return `/login?redirect=/accept-invitation&token=${token.value}`
  }
  return '/login'
})

const registerUrl = computed(() => {
  if (token.value && invitation.value?.email) {
    return `/register?redirect=/accept-invitation&token=${token.value}&email=${encodeURIComponent(invitation.value.email)}`
  }
  if (token.value) {
    return `/register?redirect=/accept-invitation&token=${token.value}`
  }
  return '/register'
})

const getRoleColor = (role?: string) => {
  const colors: Record<string, string> = {
    owner: 'purple',
    admin: 'blue',
    hr: 'green',
    finance: 'orange',
    employee: 'grey',
  }
  return colors[role || ''] || 'grey'
}

const formatDate = (dateString: string) => {
  try {
    return formatDateTimeForDisplay(dateString, {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    })
  } catch {
    return dateString
  }
}

const fetchInvitationDetails = async () => {
  if (!token.value) {
    error.value = 'Invalid invitation link. No token provided.'
    return
  }

  isLoading.value = true
  error.value = null

  try {
    const response = await apiAxios.get<{ data: Invitation }>(`/invitations/token?token=${token.value}`)
    invitation.value = response.data.data
  } catch (err) {
    const apiError = err as { response?: { data?: { message?: string } } }
    error.value = apiError?.response?.data?.message || 'Failed to load invitation details.'
  } finally {
    isLoading.value = false
  }
}

const handleAccept = async () => {
  if (!token.value) {
    error.value = 'Invalid invitation token.'
    return
  }

  if (!isAuthenticated.value) {
    router.push(loginUrl.value)
    return
  }

  isAccepting.value = true
  error.value = null

  try {
    // Accept invitation - backend will regenerate session
    await acceptInvitationMutation.mutateAsync({ token: token.value })
    isSuccess.value = true

    // Set the company in localStorage BEFORE fetching user data
    // This ensures the X-Company-ID header is correct when fetching user
    if (invitation.value?.company?.uuid) {
      companyStore.setActiveCompany(invitation.value.company.uuid)
    }

    // Fetch companies to get the updated list
    await companyStore.fetchCompanies()
  } catch (err) {
    const apiError = err as { response?: { data?: { message?: string }; status?: number } }
    const errorMessage = apiError?.response?.data?.message || (err as Error)?.message || 'Failed to accept invitation. Please try again.'

    // If authentication error, redirect to login
    if (apiError?.response?.status === 401) {
      error.value = 'Your session has expired. Please log in again.'
      setTimeout(() => {
        router.push(loginUrl.value)
      }, 2000)
    } else {
      error.value = errorMessage
    }
  } finally {
    isAccepting.value = false
  }
}

const handleRedirect = async () => {
  try {
    // Fetch CSRF cookie to ensure session is established
    await webAxios.get('/sanctum/csrf-cookie')

    // Call /api/user ONCE - Laravel is the single source of truth
    const response = await apiAxios.get<{ data: User }>('/user')
    const user = response.data.data

    // Store user in Pinia and Vue Query cache
    queryClient.setQueryData(['user'], user)
    await authStore.fetchUser()

    // Determine redirect path based on role
    let redirectPath = '/login' // Default fallback

    if (user.role === 'owner') {
      redirectPath = '/owner'
    } else if (user.role === 'admin') {
      redirectPath = '/admin'
    } else if (user.role === 'employee' || user.role === 'hr' || user.role === 'payroll') {
      redirectPath = '/employee'
    }

    // Navigate using replace to avoid back button issues
    await router.replace(redirectPath)
  } catch (err) {
    // If /api/user fails, redirect to login
    const apiError = err as { response?: { status?: number } }
    if (apiError?.response?.status === 401) {
      error.value = 'Your session has expired. Please log in again.'
    } else {
      error.value = 'Unable to load user data. Please log in again.'
    }
    await router.replace('/login')
  }
}

onMounted(() => {
  if (!token.value) {
    error.value = 'Invalid invitation link. No token provided.'
    return
  }

  fetchInvitationDetails()
})
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
}
</style>
