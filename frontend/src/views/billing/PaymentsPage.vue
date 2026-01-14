<template>
  <div class="payments-page">
    <div class="d-flex align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Payment History</h1>
        <p class="text-body-1 text-grey">View all your past payments and invoices</p>
      </div>
      <v-spacer></v-spacer>
      <v-btn-toggle v-model="statusFilter" color="primary" mandatory variant="outlined">
        <v-btn value="">All</v-btn>
        <v-btn value="paid">Paid</v-btn>
        <v-btn value="pending">Pending</v-btn>
        <v-btn value="failed">Failed</v-btn>
      </v-btn-toggle>
    </div>

    <PaymentHistory
      :payments="paymentsQuery.data.value?.data ?? []"
      :loading="paymentsQuery.isLoading.value"
      @view="handleViewPayment"
    />

    <!-- Payment Details Dialog -->
    <v-dialog v-model="showDetailsDialog" max-width="500">
      <v-card v-if="selectedPayment">
        <v-card-title class="d-flex align-center">
          <v-icon start color="primary">mdi-receipt</v-icon>
          Payment Details
        </v-card-title>

        <v-card-text>
          <v-list density="compact">
            <v-list-item>
              <template #prepend>
                <v-icon size="small">mdi-identifier</v-icon>
              </template>
              <v-list-item-title>Reference</v-list-item-title>
              <template #append>
                <span class="font-weight-medium">{{ selectedPayment.uuid.slice(0, 8) }}...</span>
              </template>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon size="small">mdi-cash</v-icon>
              </template>
              <v-list-item-title>Amount</v-list-item-title>
              <template #append>
                <span class="font-weight-bold text-primary">{{ selectedPayment.formatted_amount }}</span>
              </template>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon size="small">mdi-credit-card</v-icon>
              </template>
              <v-list-item-title>Payment Method</v-list-item-title>
              <template #append>
                <span class="font-weight-medium">{{ selectedPayment.method_display }}</span>
              </template>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon size="small">mdi-check-circle</v-icon>
              </template>
              <v-list-item-title>Status</v-list-item-title>
              <template #append>
                <v-chip :color="getStatusColor(selectedPayment.status)" size="small">
                  {{ selectedPayment.status }}
                </v-chip>
              </template>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon size="small">mdi-calendar</v-icon>
              </template>
              <v-list-item-title>Created</v-list-item-title>
              <template #append>
                <span class="font-weight-medium">{{ formatDate(selectedPayment.created_at) }}</span>
              </template>
            </v-list-item>

            <v-list-item v-if="selectedPayment.paid_at">
              <template #prepend>
                <v-icon size="small">mdi-calendar-check</v-icon>
              </template>
              <v-list-item-title>Paid</v-list-item-title>
              <template #append>
                <span class="font-weight-medium">{{ formatDate(selectedPayment.paid_at) }}</span>
              </template>
            </v-list-item>

            <v-list-item v-if="selectedPayment.description">
              <template #prepend>
                <v-icon size="small">mdi-text</v-icon>
              </template>
              <v-list-item-title>Description</v-list-item-title>
              <template #append>
                <span class="font-weight-medium">{{ selectedPayment.description }}</span>
              </template>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showDetailsDialog = false">Close</v-btn>
          <v-btn
            v-if="selectedPayment.is_pending && selectedPayment.checkout_url"
            color="primary"
            :href="selectedPayment.checkout_url"
            target="_blank"
          >
            Complete Payment
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import PaymentHistory from '@/components/billing/PaymentHistory.vue'
import { usePayments } from '@/composables/billing/useBilling'
import type { Payment, PaymentStatus } from '@/types/billing'

const statusFilter = ref('')
const paymentsQuery = usePayments(statusFilter.value || undefined)

const showDetailsDialog = ref(false)
const selectedPayment = ref<Payment | null>(null)

watch(statusFilter, () => {
  paymentsQuery.refetch()
})

const handleViewPayment = (payment: Payment) => {
  selectedPayment.value = payment
  showDetailsDialog.value = true
}

const getStatusColor = (status: PaymentStatus): string => {
  const colors: Record<PaymentStatus, string> = {
    pending: 'warning',
    processing: 'info',
    paid: 'success',
    failed: 'error',
    expired: 'grey',
    refunded: 'purple',
  }
  return colors[status] || 'grey'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.payments-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
