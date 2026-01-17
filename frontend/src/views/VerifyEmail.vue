<template>
  <v-container class="fill-height d-flex align-center justify-center">
    <v-card max-width="480" class="pa-6" elevation="3">
      <v-card-title class="text-h6 font-weight-bold mb-2">
        Email Verification
      </v-card-title>
      <v-card-text>
        <div v-if="state === 'verifying'" class="d-flex align-center">
          <v-progress-circular indeterminate color="primary" class="me-3" />
          <div>Verifying your email...</div>
        </div>

        <div v-else-if="state === 'success'" class="d-flex align-center">
          <v-icon color="success" class="me-2">mdi-check-circle</v-icon>
          <div>Your email has been verified.</div>
        </div>

        <div v-else class="d-flex align-center">
          <v-icon color="error" class="me-2">mdi-alert-circle</v-icon>
          <div>{{ errorMessage }}</div>
        </div>
      </v-card-text>
      <v-card-actions class="justify-end">
        <v-btn color="primary" variant="flat" @click="goNext" :loading="state === 'verifying'">
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
  const id = route.query.id as string
  const hash = route.query.hash as string
  const signature = route.query.signature as string
  const expires = route.query.expires as string

  if (!id || !hash || !signature || !expires) {
    state.value = 'error'
    errorMessage.value = 'Missing verification parameters.'
    return
  }

  try {
    await axios.get(`/email/verify/${id}/${hash}`, {
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
