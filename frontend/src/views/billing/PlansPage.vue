<template>
  <div class="plans-page">
    <div class="text-center mb-8">
      <h1 class="text-h3 font-weight-bold mb-2">Choose Your Plan</h1>
      <p class="text-body-1 text-grey mx-auto" style="max-width: 600px;">
        Select the plan that best fits your business needs. All plans include a free trial.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="plansQuery.isLoading.value" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
    </div>

    <!-- Plans Grid -->
    <v-row v-else justify="center" class="mb-8">
      <v-col
        v-for="plan in plansQuery.data.value"
        :key="plan.uuid"
        cols="12"
        sm="6"
        lg="3"
      >
        <v-card
          :elevation="selectedPlan?.uuid === plan.uuid ? 8 : 2"
          class="h-100"
          rounded="lg"
        >
          <v-card-text>
            <div class="d-flex align-center justify-space-between mb-2">
              <h3 class="text-h5 font-weight-bold mb-0">{{ plan.name }}</h3>
              <v-chip color="primary" variant="tonal" size="small">
                {{ plan.billing_cycle }}
              </v-chip>
            </div>
            <div class="text-h5 font-weight-bold text-primary">
              ₱{{ plan.price.toLocaleString() }}
            </div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              Up to {{ plan.max_employees }} employees
            </div>
            <v-btn
              color="primary"
              block
              :variant="selectedPlan?.uuid === plan.uuid ? 'tonal' : 'elevated'"
              :loading="subscribeMutation.isPending.value && selectedPlan?.uuid === plan.uuid"
              @click="handlePlanSelect(plan)"
            >
              {{ selectedPlan?.uuid === plan.uuid ? 'Selected' : 'Choose Plan' }}
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Payment Method Selection -->
    <v-expand-transition>
      <v-card v-if="selectedPlan" class="mx-auto mb-6" max-width="600">
        <v-card-title class="d-flex align-center">
          <v-icon start color="primary">mdi-credit-card</v-icon>
          Complete Your Purchase
        </v-card-title>

        <v-card-text>
          <div class="mb-4">
            <div class="text-subtitle-2 text-grey">Selected Plan</div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-h6 font-weight-bold">{{ selectedPlan.name }}</span>
              <span class="text-h6 font-weight-bold text-primary">
                ₱{{ selectedPlan.price.toLocaleString() }}
              </span>
            </div>
          </div>

          <v-divider class="mb-4"></v-divider>

          <div class="text-subtitle-2 text-grey mb-2">Payment Method</div>
          <v-radio-group v-model="selectedPaymentMethod" :disabled="subscribeMutation.isPending.value">
            <v-radio label="GCash" value="gcash" />
            <v-radio label="Card" value="card" />
          </v-radio-group>

          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="mt-4"
            closable
            @click:close="error = ''"
          >
            {{ error }}
          </v-alert>
        </v-card-text>

        <v-card-actions class="pa-4 pt-0">
          <v-btn variant="text" @click="selectedPlan = null">
            Cancel
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            size="large"
            :loading="subscribeMutation.isPending.value"
            :disabled="!selectedPaymentMethod"
            @click="handleSubscribe"
          >
            <v-icon start>mdi-lock</v-icon>
            Proceed to Payment
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-expand-transition>

    <!-- Current Subscription Notice -->
    <v-alert
      v-if="subscriptionQuery.data.value"
      type="info"
      variant="tonal"
      class="mx-auto mt-4"
      max-width="600"
    >
      <v-alert-title>You already have an active subscription</v-alert-title>
      Selecting a new plan will replace your current subscription after payment.
    </v-alert>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePlans, useSubscription, useSubscribe } from '@/composables/billing/useBilling'
import type { Plan } from '@/types/billing'

const plansQuery = usePlans()
const subscriptionQuery = useSubscription()
const subscribeMutation = useSubscribe()

const selectedPlan = ref<Plan | null>(null)
const selectedPaymentMethod = ref<'gcash' | 'card' | null>(null)
const error = ref('')

const handlePlanSelect = (plan: Plan) => {
  selectedPlan.value = plan
  selectedPaymentMethod.value = null
  error.value = ''
}

const handleSubscribe = async () => {
  if (!selectedPlan.value || !selectedPaymentMethod.value) {
    error.value = 'Please select a payment method'
    return
  }

  try {
    error.value = ''

    const result = await subscribeMutation.mutateAsync({
      plan_uuid: selectedPlan.value.uuid,
      payment_method: selectedPaymentMethod.value,
    })

    // Redirect to PayMongo checkout
    const checkoutUrl =
      (result as { checkout_url?: string })?.checkout_url ||
      (result as { data?: { checkout_url?: string } })?.data?.checkout_url
    if (checkoutUrl) {
      window.location.href = checkoutUrl
    }
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    error.value = axiosError.response?.data?.message || 'Failed to create checkout. Please try again.'
  }
}
</script>

<style scoped>
.plans-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}
</style>
