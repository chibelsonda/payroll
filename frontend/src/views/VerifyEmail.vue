<template>
  <v-container class="fill-height d-flex align-center justify-center" fluid>
    <v-card max-width="520" class="pa-6" elevation="4" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between mb-2">
        <div class="d-flex align-center gap-3">
          <v-avatar color="primary" variant="tonal" size="44">
            <v-icon color="primary">mdi-email-check</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">Email Verification</div>
            <div class="text-caption text-medium-emphasis">We’re confirming your HRIS account</div>
          </div>
        </div>
      </v-card-title>
      <v-card-text class="pt-2">
        <div v-if="state === 'verifying'" class="d-flex align-center">
          <v-progress-circular indeterminate color="primary" class="me-3" />
          <div class="text-body-2 text-medium-emphasis">Verifying your email...</div>
        </div>

        <v-alert
          v-else-if="state === 'success'"
          type="success"
          variant="tonal"
          border="start"
          density="comfortable"
          class="mb-3"
        >
          <div class="d-flex align-center">
            <v-icon class="me-2" color="success">mdi-check-circle</v-icon>
            <div>
              <div class="font-weight-medium">Email verified</div>
              <div class="text-body-2 text-medium-emphasis">You’re all set. Continue to finish setup.</div>
            </div>
          </div>
        </v-alert>

        <v-alert
          v-else
          type="error"
          variant="tonal"
          border="start"
          density="comfortable"
          class="mb-3"
        >
          <div class="d-flex align-center">
            <v-icon class="me-2" color="error">mdi-alert-circle</v-icon>
            <div>
              <div class="font-weight-medium">Verification failed</div>
              <div class="text-body-2 text-medium-emphasis">{{ errorMessage }}</div>
            </div>
          </div>
        </v-alert>

        <div class="text-caption text-medium-emphasis">
          If the button doesn't work, confirm you opened the latest link from your email. Verification links expire after 24 hours.
        </div>
      </v-card-text>
      <v-card-actions class="justify-end pt-2">
        <v-btn
          color="primary"
          variant="flat"
          class="px-5"
          @click="goNext"
          :loading="state === 'verifying'"
        >
          Continue
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/lib/axios'
import { extractErrorMessage } from '@/composables'
import { useNotification } from '@/composables'
import { useAuthStore } from '@/stores/auth'

type State = 'verifying' | 'success' | 'error'

const route = useRoute()
const router = useRouter()
const { showNotification } = useNotification()
const auth = useAuthStore()

const state = ref<State>('verifying')
const errorMessage = ref('The verification link is invalid or has expired.')

const verifyEmail = async () => {
  const uuid = route.query.uuid as string
  const hash = route.query.hash as string
  const signature = route.query.signature as string
  const expires = route.query.expires as string

  if (!uuid || !hash || !signature || !expires) {
    state.value = 'error'
    errorMessage.value = 'Missing verification parameters.'
    return
  }

  try {
    await axios.get(`/email/verify/${uuid}/${hash}`, {
      params: { signature, expires },
    })

    state.value = 'success'
    showNotification('Email verified successfully', 'success')
    await auth.fetchUser()
  } catch (error: unknown) {
    state.value = 'error'
    errorMessage.value = extractErrorMessage(error, 'The verification link is invalid or has expired.')

  }
}

const goNext = () => {
  if (auth.user) {
    router.push('/onboarding/create-company')
  } else {
    router.push('/login')
  }
}

onMounted(() => {
  verifyEmail()
})
</script>
