<template>
  <div>
    <v-container fluid class="px-4 py-6 bg-grey-lighten-5">
      <v-row justify="center">
        <v-col cols="12" lg="10">
          <div class="text-center mb-8">
            <h1 class="text-h4 font-weight-bold text-primary mb-2">Choose Your Plan</h1>
            <p class="text-body-1 text-medium-emphasis">Select the perfect plan for your business needs</p>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="text-center py-12">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <p class="mt-4 text-h6 text-medium-emphasis">Loading plans...</p>
          </div>

          <!-- Error State -->
          <v-alert
            v-else-if="error"
            type="error"
            variant="tonal"
            class="mb-6"
            rounded="lg"
            prominent
          >
            <div class="d-flex align-center">
              <v-icon class="me-3" size="24">mdi-alert-circle</v-icon>
              <div class="flex-grow-1">
                <div class="font-weight-medium text-h6">Failed to load plans</div>
                <div class="text-body-2">{{ error.message }}</div>
              </div>
              <v-btn variant="text" color="error" size="large" @click="refetch">Retry</v-btn>
            </div>
          </v-alert>

          <!-- Plans Grid -->
          <v-row v-else class="plans-grid">
            <v-col
              v-for="(plan, index) in plans"
              :key="plan.uuid"
              cols="12"
              sm="12"
              md="4"
            >
              <v-card
                :elevation="selectedPlanUuid === plan.uuid ? 8 : 2"
                rounded="lg"
                :class="[
                  'plan-card h-100 position-relative overflow-hidden',
                  {
                    'selected-plan': selectedPlanUuid === plan.uuid,
                    'popular-plan': index === 1
                  }
                ]"
              >
                <!-- Popular Badge -->
                <v-chip
                  v-if="index === 1"
                  color="warning"
                  size="small"
                  class="popular-badge"
                  variant="elevated"
                >
                  <v-icon start size="16">mdi-star</v-icon>
                  Most Popular
                </v-chip>

                <!-- Card Header with Gradient -->
                <div class="plan-header">
                  <div class="plan-icon mb-3">
                    <v-icon size="48" color="white">
                      {{ getPlanIcon(plan.max_employees) }}
                    </v-icon>
                  </div>
                  <h3 class="text-h5 font-weight-bold text-white mb-1">{{ plan.name }}</h3>
                  <div class="plan-price">
                    <span class="currency">₱</span>
                    <span class="amount">{{ plan.price.toLocaleString() }}</span>
                    <span class="period">/{{ plan.billing_cycle }}</span>
                  </div>
                </div>

                <v-card-text class="flex-grow-1 pa-6">
                  <!-- Features -->
                  <div class="features-list">
                    <div class="feature-item">
                      <v-icon color="success" size="20" class="me-3">mdi-check-circle</v-icon>
                      <span>Up to {{ plan.max_employees }} employees</span>
                    </div>
                    <div class="feature-item">
                      <v-icon color="success" size="20" class="me-3">mdi-check-circle</v-icon>
                      <span>Payroll processing</span>
                    </div>
                    <div class="feature-item">
                      <v-icon color="success" size="20" class="me-3">mdi-check-circle</v-icon>
                      <span>Employee management</span>
                    </div>
                    <div class="feature-item">
                      <v-icon color="success" size="20" class="me-3">mdi-check-circle</v-icon>
                      <span>Reports & analytics</span>
                    </div>
                    <div class="feature-item">
                      <v-icon color="success" size="20" class="me-3">mdi-check-circle</v-icon>
                      <span>24/7 support</span>
                    </div>
                  </div>
                </v-card-text>

                <v-card-actions class="pa-6 pt-0">
                  <v-btn
                    v-show="selectedPlanUuid !== plan.uuid"
                    block
                    size="large"
                    color="primary"
                    variant="elevated"
                    @click.stop="openWizard(plan)"
                    rounded="lg"
                  >
                    Select Plan
                  </v-btn>
                  <v-btn
                    v-show="selectedPlanUuid === plan.uuid"
                    block
                    size="large"
                    color="white"
                    variant="flat"
                    class="selected-btn"
                    prepend-icon="mdi-check"
                    @click.stop="openWizard(plan)"
                    rounded="lg"
                  >
                    Selected
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>

    <!-- Subscribe Wizard -->
    <SubscribeWizard
      v-if="selectedPlan"
      v-model="showWizard"
      :initial-plan="selectedPlan"
      @success="handleSubscriptionSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePlans } from '@/composables/billing/useBilling'
import SubscribeWizard from './SubscribeWizard.vue'
import type { Plan } from '@/types/billing'

const { data: plansData, isLoading, error, refetch } = usePlans()

const plans = computed(() => plansData.value || [])
const selectedPlan = ref<Plan | null>(null)
const selectedPlanUuid = ref<string | null>(null)
const showWizard = ref(false)

const openWizard = (plan: Plan) => {
  selectedPlan.value = plan
  selectedPlanUuid.value = plan.uuid
  showWizard.value = true
}

const getPlanIcon = (maxEmployees: number): string => {
  if (maxEmployees <= 10) return 'mdi-account'
  if (maxEmployees <= 50) return 'mdi-account-group'
  if (maxEmployees <= 100) return 'mdi-office-building'
  return 'mdi-domain'
}

const handleSubscriptionSuccess = () => {
  // Refresh subscription status, show toast, etc.
  showWizard.value = false
  selectedPlan.value = null
  refetch()
}
</script>

<style scoped>
.plans-grid {
  gap: 0;
}

.plan-card {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid transparent;
  position: relative;
  overflow: hidden;
}

.plan-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15) !important;
}

.plan-card.selected-plan {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 8px 24px rgba(var(--v-theme-primary), 0.3) !important;
}

.plan-card.popular-plan {
  border: 3px solid #ff6b35;
  box-shadow: 0 8px 24px rgba(255, 107, 53, 0.2) !important;
}

.plan-card.popular-plan:hover {
  box-shadow: 0 12px 32px rgba(255, 107, 53, 0.3) !important;
}

.popular-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  font-weight: 600;
}

.plan-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.85) 0%, rgba(var(--v-theme-primary), 0.75) 100%);
  padding: 2rem 1.5rem 1.5rem;
  text-align: center;
  position: relative;
}

.plan-card.popular-plan .plan-header {
  background: linear-gradient(135deg, rgba(255, 107, 53, 0.85) 0%, rgba(247, 147, 30, 0.85) 100%);
  box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.1);
}

.plan-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
}

.plan-price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  color: white;
  margin-top: 0.5rem;
}

.plan-price .currency {
  font-size: 1.5rem;
  font-weight: 300;
  margin-right: 0.25rem;
}

.plan-price .amount {
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
}

.plan-price .period {
  font-size: 1rem;
  font-weight: 400;
  opacity: 0.9;
  margin-left: 0.25rem;
}

.features-list {
  padding: 0;
}

.feature-item {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
  font-size: 0.95rem;
  line-height: 1.4;
}

.feature-item:last-child {
  margin-bottom: 0;
}

.selected-btn {
  background: rgb(var(--v-theme-primary)) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.4) !important;
}

.payment-radio-group {
  width: 100%;
}

.payment-radio {
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid rgb(var(--v-theme-surface-variant));
  border-radius: 12px;
  transition: all 0.2s;
}

.payment-radio:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.payment-radio.v-radio--is-selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}

.selected-plan-summary {
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.1) 0%, rgba(var(--v-theme-primary), 0.05) 100%);
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-primary), 0.2);
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .plans-grid {
    gap: 1.5rem;
  }
}

@media (max-width: 600px) {
  .plans-grid {
    gap: 1rem;
  }

  .plan-header {
    padding: 1.5rem 1rem 1rem;
  }

  .plan-price .amount {
    font-size: 2rem;
  }
}
</style>
