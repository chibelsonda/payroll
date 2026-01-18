<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <h1 class="text-h6 font-weight-bold">Payment History</h1>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading payments...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="ma-4"
              rounded="lg"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load payments</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Payments Table -->
            <v-data-table
              v-else
              :items="payments"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
            >
              <template v-slot:item.amount="{ item }">
                <span class="font-weight-medium">
                  ₱{{ item.amount.toLocaleString() }}
                </span>
              </template>

              <template v-slot:item.method="{ item }">
                <v-chip
                  size="small"
                  :color="getMethodColor(item.method)"
                  variant="tonal"
                >
                  {{ item.method.toUpperCase() }}
                </v-chip>
              </template>

              <template v-slot:item.status="{ item }">
                <v-chip
                  size="small"
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                >
                  {{ item.status.toUpperCase() }}
                </v-chip>
              </template>

              <template v-slot:item.paid_at="{ item }">
                {{ item.paid_at ? new Date(item.paid_at).toLocaleString() : '-' }}
              </template>

              <template v-slot:item.created_at="{ item }">
                {{ new Date(item.created_at).toLocaleString() }}
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePayments } from '@/composables/billing/useBilling'
import type { Payment } from '@/types/billing'

const page = ref(1)
const { data, isLoading, error, refetch } = usePayments(page)

const payments = computed(() => data.value?.data || [])
const meta = computed(() => data.value?.meta)

const headers = [
  { title: 'Amount', key: 'amount', sortable: true },
  { title: 'Method', key: 'method', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Paid At', key: 'paid_at', sortable: true },
  { title: 'Created At', key: 'created_at', sortable: true },
]

const getMethodColor = (method: string) => {
  const colors: Record<string, string> = {
    gcash: 'green',
    card: 'blue',
    maya: 'purple',
  }
  return colors[method.toLowerCase()] || 'grey'
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    paid: 'success',
    pending: 'warning',
    failed: 'error',
    expired: 'error',
  }
  return colors[status.toLowerCase()] || 'grey'
}
</script>
