<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <h1 class="text-h6 font-weight-bold">Billing & Subscription</h1>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-4">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading subscription...</p>
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
                  <div class="font-weight-medium">Failed to load subscription</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- No Subscription -->
            <v-alert
              v-else-if="!subscription"
              type="warning"
              variant="tonal"
              class="mb-4"
              rounded="lg"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">No Active Subscription</div>
                  <div class="text-caption">Please subscribe to a plan to continue using the service.</div>
                </div>
                <v-btn color="primary" @click="$router.push('/owner/billing/plans')">
                  View Plans
                </v-btn>
              </div>
            </v-alert>

            <!-- Active Subscription -->
            <div v-else>
              <v-row>
                <v-col cols="12" md="6">
                  <v-card variant="outlined" rounded="lg">
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                      Current Plan
                    </v-card-title>
                    <v-card-text>
                      <div class="text-h5 font-weight-bold primary--text mb-2">
                        {{ subscription.plan?.name || 'N/A' }}
                      </div>
                      <div class="text-body-2 text-medium-emphasis mb-2">
                        ₱{{ subscription.plan?.price.toLocaleString() }} / {{ subscription.plan?.billing_cycle }}
                      </div>
                      <v-chip
                        :color="getStatusColor(subscription.status)"
                        variant="tonal"
                        size="small"
                      >
                        {{ subscription.status.toUpperCase() }}
                      </v-chip>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="6">
                  <v-card variant="outlined" rounded="lg">
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                      Subscription Details
                    </v-card-title>
                    <v-card-text>
                      <div class="mb-2">
                        <span class="text-medium-emphasis">Starts:</span>
                        <span class="ml-2">{{ subscription.starts_at ? new Date(subscription.starts_at).toLocaleDateString() : 'N/A' }}</span>
                      </div>
                      <div class="mb-2">
                        <span class="text-medium-emphasis">Ends:</span>
                        <span class="ml-2">{{ subscription.ends_at ? new Date(subscription.ends_at).toLocaleDateString() : 'N/A' }}</span>
                      </div>
                      <div v-if="subscription.trial_ends_at" class="mb-2">
                        <span class="text-medium-emphasis">Trial Ends:</span>
                        <span class="ml-2">{{ new Date(subscription.trial_ends_at).toLocaleDateString() }}</span>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-row class="mt-2">
                <v-col cols="12">
                  <v-btn
                    color="primary"
                    @click="$router.push('/owner/billing/plans')"
                  >
                    Change Plan
                  </v-btn>
                  <v-btn
                    variant="outlined"
                    class="ml-2"
                    @click="$router.push('/owner/billing/payments')"
                  >
                    View Payment History
                  </v-btn>
                </v-col>
              </v-row>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { useSubscription } from '@/composables/billing/useBilling'

const { data: subscription, isLoading, error, refetch } = useSubscription()

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    active: 'success',
    trialing: 'info',
    pending: 'warning',
    past_due: 'error',
    canceled: 'grey',
    expired: 'error',
  }
  return colors[status.toLowerCase()] || 'grey'
}
</script>
