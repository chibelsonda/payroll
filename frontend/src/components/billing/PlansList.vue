<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <h1 class="text-h6 font-weight-bold">Subscription Plans</h1>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-4">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading plans...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              rounded="lg"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load plans</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Plans Grid -->
            <v-row v-else>
              <v-col
                v-for="plan in plans"
                :key="plan.uuid"
                cols="12"
                md="4"
              >
                <v-card
                  elevation="2"
                  rounded="lg"
                  :class="{ 'border-primary': selectedPlan?.uuid === plan.uuid }"
                  class="plan-card"
                  @click="selectPlan(plan)"
                >
                  <v-card-title class="text-h6 font-weight-bold">
                    {{ plan.name }}
                  </v-card-title>
                  <v-card-subtitle class="text-h4 font-weight-bold primary--text pt-2">
                    ₱{{ plan.price.toLocaleString() }}
                    <span class="text-body-2 text-medium-emphasis">/{{ plan.billing_cycle }}</span>
                  </v-card-subtitle>
                  <v-card-text>
                    <div class="mb-2">
                      <v-icon size="small" class="me-2">mdi-account-group</v-icon>
                      Up to {{ plan.max_employees }} employees
                    </div>
                  </v-card-text>
                  <v-card-actions>
                    <v-btn
                      block
                      color="primary"
                      :variant="selectedPlan?.uuid === plan.uuid ? 'flat' : 'outlined'"
                      @click.stop="selectPlan(plan)"
                    >
                      {{ selectedPlan?.uuid === plan.uuid ? 'Selected' : 'Select Plan' }}
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>

            <!-- Payment Method Selection -->
            <v-row v-if="selectedPlan" class="mt-4">
              <v-col cols="12">
                <v-card variant="outlined" rounded="lg">
                  <v-card-title class="text-subtitle-1 font-weight-bold">
                    Select Payment Method
                  </v-card-title>
                  <v-card-text>
                    <v-radio-group v-model="paymentMethod" inline>
                      <v-radio
                        label="GCash"
                        value="gcash"
                        color="primary"
                      >
                        <template v-slot:label>
                          <div class="d-flex align-center">
                            <v-icon class="me-2">mdi-cellphone</v-icon>
                            <span>GCash</span>
                          </div>
                        </template>
                      </v-radio>
                      <v-radio
                        label="Card"
                        value="card"
                        color="primary"
                      >
                        <template v-slot:label>
                          <div class="d-flex align-center">
                            <v-icon class="me-2">mdi-credit-card</v-icon>
                            <span>Credit/Debit Card</span>
                          </div>
                        </template>
                      </v-radio>
                    </v-radio-group>
                  </v-card-text>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                      color="primary"
                      :loading="subscribeMutation.isPending.value"
                      :disabled="!paymentMethod"
                      @click="handleSubscribe"
                    >
                      Subscribe Now
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePlans, useSubscribe } from '@/composables/billing/useBilling'
import type { Plan } from '@/types/billing'

const { data: plansData, isLoading, error, refetch } = usePlans()
const subscribeMutation = useSubscribe()

const plans = computed(() => plansData.value || [])
const selectedPlan = ref<Plan | null>(null)
const paymentMethod = ref<'gcash' | 'card' | null>(null)

const selectPlan = (plan: Plan) => {
  selectedPlan.value = plan
}

const handleSubscribe = async () => {
  if (!selectedPlan.value || !paymentMethod.value) return

  try {
    const result = await subscribeMutation.mutateAsync({
      plan_uuid: selectedPlan.value.uuid,
      payment_method: paymentMethod.value,
      success_url: `${window.location.origin}/billing/success`,
      cancel_url: `${window.location.origin}/billing`,
    })

    if (result.checkout_url) {
      window.location.href = result.checkout_url
    }
  } catch (err: any) {
    console.error('Subscription failed:', err)
  }
}
</script>

<style scoped>
.plan-card {
  cursor: pointer;
  transition: all 0.2s;
}

.plan-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.border-primary {
  border: 2px solid rgb(var(--v-theme-primary)) !important;
}
</style>
