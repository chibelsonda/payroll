<template>
  <v-container class="py-10" max-width="720">
    <v-card class="pa-6">
      <v-card-title class="text-h5 font-weight-bold mb-2">
        Payment Cancellation
      </v-card-title>
      <v-card-subtitle class="mb-4">
        We are processing your cancellation request.
      </v-card-subtitle>

      <div class="d-flex align-center mb-4" v-if="isSubmitting">
        <v-progress-circular indeterminate color="primary" class="mr-3" />
        <div>Canceling payment...</div>
      </div>

      <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
        {{ error }}
      </v-alert>

      <v-alert v-else type="success" variant="tonal" class="mb-4">
        Payment cancelled successfully.
      </v-alert>

      <div class="d-flex flex-wrap gap-3">
        <v-btn color="primary mr-2" variant="elevated" @click="retryPayment" :disabled="isSubmitting">
          Retry Payment
        </v-btn>
        <v-btn color="secondary" variant="outlined" @click="goBack" :disabled="isSubmitting">
          Back to Billing
        </v-btn>
      </div>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useBillingCancel } from '@/composables/billing/useBillingCancel'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const error = ref<string | null>(null)
const { mutateAsync: cancelPaymentMutation, isPending } = useBillingCancel()
const isSubmitting = computed(() => loading.value || isPending.value)

const paymentIntentId = computed(() => {
  return (
    (route.query.payment_intent_id as string | undefined) ||
    (route.query.reference_id as string | undefined) ||
    localStorage.getItem('last_checkout_reference') ||
    undefined
  )
})

const cancelPayment = async () => {
  if (!paymentIntentId.value) {
    error.value = 'Missing payment reference.'
    loading.value = false
    return
  }

  try {
    await cancelPaymentMutation(paymentIntentId.value)
  } catch (err: unknown) {
    const axiosErr = err as { response?: { data?: { message?: string } }; message?: string }
    const message =
      axiosErr.response?.data?.message ||
      axiosErr.message ||
      (err instanceof Error ? err.message : null) ||
      'Failed to cancel payment.'
    error.value = message
  } finally {
    loading.value = false
  }
}

const retryPayment = () => {
  router.push({ name: 'owner-billing-plans' })
}

const goBack = () => {
  router.push({ name: 'owner-billing-plans' })
}

onMounted(cancelPayment)
</script>
