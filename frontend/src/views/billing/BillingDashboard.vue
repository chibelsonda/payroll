<template>
  <div class="billing-dashboard">
    <div class="d-flex align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Billing</h1>
        <p class="text-body-1 text-grey">Manage your subscription and payments</p>
      </div>
    </div>

    <!-- Payment Status Alert -->
    <v-alert
      v-if="paymentStatus === 'success'"
      type="success"
      variant="tonal"
      closable
      class="mb-6"
      @click:close="clearPaymentStatus"
    >
      <v-alert-title>Payment Successful!</v-alert-title>
      Your subscription has been activated. Thank you for your payment.
    </v-alert>

    <v-alert
      v-if="paymentStatus === 'canceled'"
      type="warning"
      variant="tonal"
      closable
      class="mb-6"
      @click:close="clearPaymentStatus"
    >
      <v-alert-title>Payment Canceled</v-alert-title>
      Your payment was not completed. You can try again anytime.
    </v-alert>

    <!-- Loading State -->
    <div v-if="subscriptionQuery.isLoading.value" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
    </div>

    <template v-else>
      <!-- Subscription Status -->
      <SubscriptionStatus
        :subscription="subscriptionQuery.data.value?.data ?? null"
        class="mb-6"
        @canceled="subscriptionQuery.refetch()"
      />

      <!-- Billing Stats -->
      <v-row v-if="statsQuery.data.value" class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-avatar color="success" variant="tonal" class="me-4">
                <v-icon>mdi-cash-check</v-icon>
              </v-avatar>
              <div>
                <div class="text-h6 font-weight-bold">
                  ₱{{ formatNumber(statsQuery.data.value.total_paid) }}
                </div>
                <div class="text-caption text-grey">Total Paid</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-avatar color="warning" variant="tonal" class="me-4">
                <v-icon>mdi-clock-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-h6 font-weight-bold">
                  ₱{{ formatNumber(statsQuery.data.value.total_pending) }}
                </div>
                <div class="text-caption text-grey">Pending</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-avatar color="primary" variant="tonal" class="me-4">
                <v-icon>mdi-receipt</v-icon>
              </v-avatar>
              <div>
                <div class="text-h6 font-weight-bold">
                  {{ statsQuery.data.value.payment_count }}
                </div>
                <div class="text-caption text-grey">Payments Made</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-avatar color="info" variant="tonal" class="me-4">
                <v-icon>mdi-calendar-clock</v-icon>
              </v-avatar>
              <div>
                <div class="text-h6 font-weight-bold">
                  {{ subscriptionQuery.data.value?.data?.days_remaining ?? 'N/A' }}
                </div>
                <div class="text-caption text-grey">Days Remaining</div>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Payment History -->
      <PaymentHistory
        :payments="paymentsQuery.data.value?.data ?? []"
        :loading="paymentsQuery.isLoading.value"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SubscriptionStatus from '@/components/billing/SubscriptionStatus.vue'
import PaymentHistory from '@/components/billing/PaymentHistory.vue'
import { useSubscription, usePayments, useBillingStats } from '@/composables/billing/useBilling'

const route = useRoute()
const router = useRouter()

const subscriptionQuery = useSubscription()
const paymentsQuery = usePayments()
const statsQuery = useBillingStats()

const paymentStatus = computed(() => route.query.status as string | undefined)

const clearPaymentStatus = () => {
  router.replace({ query: {} })
}

const formatNumber = (num: number): string => {
  return num.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

onMounted(() => {
  // Refetch data if coming back from payment
  if (paymentStatus.value === 'success') {
    subscriptionQuery.refetch()
    paymentsQuery.refetch()
    statsQuery.refetch()
  }
})
</script>

<style scoped>
.billing-dashboard {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
