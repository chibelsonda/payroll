<template>
  <v-container class="py-12">
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card rounded="xl" elevation="4" class="pa-6 text-center">
          <v-progress-circular
            v-if="status === 'pending' && !isTimeout"
            indeterminate
            color="primary"
            size="64"
            class="mb-4"
          />
          <v-icon
            v-else-if="status === 'paid'"
            color="success"
            size="72"
            class="mb-4"
          >
            mdi-check-decagram
          </v-icon>
          <v-icon
            v-else-if="status === 'failed' || isTimeout"
            color="error"
            size="72"
            class="mb-4"
          >
            mdi-alert-circle
          </v-icon>

          <h2 class="text-h5 font-weight-bold mb-2">
            <span v-if="status === 'pending' && !isTimeout">Processing your payment…</span>
            <span v-else-if="status === 'paid'">Payment completed</span>
            <span v-else>Payment pending/failed</span>
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-6">
            <span v-if="status === 'pending' && !isTimeout">
              Please wait while we confirm your payment via PayMongo. This may take a few moments.
            </span>
            <span v-else-if="status === 'paid'">
              Your subscription will be activated shortly. You can return to your dashboard.
            </span>
            <span v-else>
              We could not confirm your payment yet. You may retry or return to billing.
            </span>
          </p>

          <div v-if="status === 'pending' && !isTimeout">
            <v-chip color="primary" variant="tonal" class="mb-4" prepend-icon="mdi-timer-sand">
              Checking status…
            </v-chip>
          </div>

          <div class="d-flex justify-center gap-3 mt-2">
            <v-btn color="primary" variant="elevated" @click="goToDashboard">
              Go to Dashboard
            </v-btn>
            <v-btn color="secondary" variant="text" @click="goToBilling">
              Back to Billing
            </v-btn>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/lib/axios'

const route = useRoute()
const router = useRouter()

const status = ref<'pending' | 'paid' | 'failed'>('pending')
const isTimeout = ref(false)

let intervalId: ReturnType<typeof setInterval> | null = null
let timeoutId: ReturnType<typeof setTimeout> | null = null

const pollStatus = async () => {
  const referenceId =
    (route.query.reference_id as string) ||
    (route.query.reference as string) ||
    localStorage.getItem('last_checkout_reference')

  if (!referenceId) {
    status.value = 'failed'
    return
  }

  try {
    const res = await axios.get('/billing/status', {
      params: { reference_id: referenceId },
    })

    status.value = res.data?.data?.status || 'pending'

    if (status.value !== 'pending') {
      stopPolling()
    }
  } catch (err) {
    // Keep pending on errors, allow timeout to handle fallback
    console.error('Status check failed', err)
  }
}

const stopPolling = () => {
  if (intervalId) clearInterval(intervalId)
  if (timeoutId) clearTimeout(timeoutId)
}

const goToDashboard = () => {
  router.push('/owner')
}

const goToBilling = () => {
  router.push('/owner/billing')
}

onMounted(() => {
  console.info('Billing success page loaded')
  // Start polling every 3s
  intervalId = setInterval(pollStatus, 3000)
  // Timeout after 60s
  timeoutId = setTimeout(() => {
    isTimeout.value = true
    stopPolling()
  }, 60000)

  // Initial poll
  pollStatus()
})

onBeforeUnmount(() => {
  stopPolling()
})
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}
</style>
