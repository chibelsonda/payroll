<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    max-width="720"
    transition="fade-transition"
    scrim="rgba(0,0,0,0.78)"
    close-on-esc
  >
    <v-card rounded="xl" class="wizard-card">
      <!-- Header with Step Indicator -->
      <div class="wizard-header pa-2">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div>
            <div class="text-overline text-medium-emphasis mb-1">Subscription Wizard</div>
            <h2 class="text-h5 font-weight-bold mb-0">Complete Your Subscription</h2>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="closeWizard" />
        </div>
        <v-stepper v-model="currentStep" class="mt-4" hide-actions density="compact">
          <v-stepper-header>
            <v-stepper-item
              v-for="step in totalSteps"
              :key="step"
              :complete="currentStep > step"
              :step="step"
              :color="currentStep >= step ? 'primary' : 'grey-lighten-2'"
            />
          </v-stepper-header>
        </v-stepper>
      </div>

      <!-- Plan summary strip -->
      <div class="plan-summary px-6 py-4 d-flex align-center gap-4">
        <v-avatar color="primary" size="44">
          <v-icon color="white">mdi-package-variant</v-icon>
        </v-avatar>
        <div class="flex-grow-1">
          <div class="text-body-1 font-weight-bold">{{ initialPlan.name }}</div>
          <div class="text-caption text-medium-emphasis">
            ₱{{ initialPlan.price.toLocaleString() }} / {{ initialPlan.billing_cycle }} • Up to {{ initialPlan.max_employees }} employees
          </div>
        </div>
        <v-chip color="primary" variant="elevated" size="small" prepend-icon="mdi-shield-check" class="text-uppercase">
          Secure
        </v-chip>
      </div>

      <!-- Step Content -->
      <v-card-text class="px-6 py-6">
        <!-- Step 1: Confirm Plan -->
        <div v-if="currentStep === 1" class="step-content">
          <h3 class="text-h6 font-weight-bold mb-6">Confirm Your Plan</h3>
          <v-card variant="outlined" rounded="lg" class="mb-6">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon color="primary" size="32" class="me-3">mdi-package-variant</v-icon>
                <div>
                  <h4 class="text-h6 font-weight-bold">{{ initialPlan.name }}</h4>
                  <p class="text-caption text-medium-emphasis mb-0">{{ initialPlan.max_employees }} employees</p>
                </div>
              </div>
              <v-divider class="my-4" />
              <div class="d-flex justify-space-between align-center">
                <span class="text-body-1">Monthly Price</span>
                <span class="text-h5 font-weight-bold primary--text">₱{{ initialPlan.price.toLocaleString() }}</span>
              </div>
            </v-card-text>
          </v-card>
          <p class="text-body-2 text-medium-emphasis">
            You're about to subscribe to the {{ initialPlan.name }} plan. Click continue to proceed.
          </p>
        </div>

        <!-- Step 2: Choose Payment Method -->
        <div v-else-if="currentStep === 2" class="step-content">
          <h3 class="text-h6 font-weight-bold mb-6">Select Payment Method</h3>
          <v-radio-group v-model="paymentMethod" class="payment-radio-group">
            <v-radio
              label="GCash"
              value="gcash"
              color="primary"
              class="payment-radio mb-4"
            >
              <template v-slot:label>
                <div class="d-flex align-center w-100">
                  <v-avatar size="48" color="success" class="me-3">
                    <v-icon color="white" size="24">mdi-cellphone</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="font-weight-medium text-body-1">GCash</div>
                    <div class="text-caption text-medium-emphasis">Fast and secure mobile wallet payments</div>
                  </div>
                </div>
              </template>
            </v-radio>
            <v-radio
              label="Card"
              value="card"
              color="primary"
              class="payment-radio"
            >
              <template v-slot:label>
                <div class="d-flex align-center w-100">
                  <v-avatar size="48" color="info" class="me-3">
                    <v-icon color="white" size="24">mdi-credit-card</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="font-weight-medium text-body-1">Credit/Debit Card</div>
                    <div class="text-caption text-medium-emphasis">Visa, Mastercard, and more</div>
                  </div>
                </div>
              </template>
            </v-radio>
          </v-radio-group>
        </div>

        <!-- Step 3: Payment Details -->
        <div v-else-if="currentStep === 3" class="step-content">
          <h3 class="text-h6 font-weight-bold mb-6">Payment Details</h3>
          <div v-if="paymentMethod === 'gcash'" class="text-center py-8">
            <v-icon color="success" size="64" class="mb-4">mdi-check-circle</v-icon>
            <h4 class="text-h6 font-weight-bold mb-2">GCash Selected</h4>
            <p class="text-body-2 text-medium-emphasis max-w-400">
              You will be securely redirected to GCash to complete your payment after confirmation.
            </p>
          </div>
          <div v-else class="card-form">
            <v-text-field
              v-model="cardForm.name"
              label="Cardholder Name"
              placeholder="John Doe"
              variant="outlined"
              class="mb-4"
              @blur="validateCardName"
            hide-details
            />
            <v-text-field
              v-model="cardForm.number"
              label="Card Number"
              placeholder="4111 1111 1111 1111"
              variant="outlined"
              maxlength="19"
              @input="formatCardNumber"
              @blur="validateCardNumber"
              class="mb-4"
            hide-details
            append-inner-icon="mdi-credit-card-outline"
            />
            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model="cardForm.expiry"
                  label="Expiry (MM/YY)"
                  placeholder="12/25"
                  variant="outlined"
                  maxlength="5"
                  @input="formatExpiry"
                  @blur="validateExpiry"
                hide-details
                append-inner-icon="mdi-calendar"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="cardForm.cvc"
                  label="CVC"
                  placeholder="123"
                  variant="outlined"
                  type="password"
                  maxlength="4"
                  @blur="validateCVC"
                hide-details
                append-inner-icon="mdi-shield-lock-outline"
                />
              </v-col>
            </v-row>
            <v-alert v-if="cardErrors.any" type="error" variant="tonal" class="mt-4">
              <div v-if="cardErrors.name" class="text-caption mb-2">{{ cardErrors.name }}</div>
              <div v-if="cardErrors.number" class="text-caption mb-2">{{ cardErrors.number }}</div>
              <div v-if="cardErrors.expiry" class="text-caption mb-2">{{ cardErrors.expiry }}</div>
              <div v-if="cardErrors.cvc" class="text-caption">{{ cardErrors.cvc }}</div>
            </v-alert>
          <div class="d-flex align-center mt-4 security-hint">
            <v-icon size="18" color="primary" class="me-2">mdi-lock</v-icon>
            <span class="text-caption text-medium-emphasis">Payments are secured and encrypted.</span>
          </div>
          </div>
        </div>

        <!-- Step 4: Review & Confirm -->
        <div v-else-if="currentStep === 4" class="step-content">
          <h3 class="text-h6 font-weight-bold mb-6">Review Your Subscription</h3>
          <v-card variant="outlined" rounded="lg" class="mb-6">
            <v-card-text class="pa-6">
              <div class="review-item mb-4">
                <span class="text-body-2 text-medium-emphasis">Plan</span>
                <p class="text-h6 font-weight-bold ma-0">{{ initialPlan.name }}</p>
              </div>
              <v-divider class="my-4" />
              <div class="review-item mb-4">
                <span class="text-body-2 text-medium-emphasis">Monthly Price</span>
                <p class="text-h5 font-weight-bold primary--text ma-0">₱{{ initialPlan.price.toLocaleString() }}</p>
              </div>
              <v-divider class="my-4" />
              <div class="review-item">
                <span class="text-body-2 text-medium-emphasis">Payment Method</span>
                <p class="text-body-1 font-weight-bold ma-0">
                  <v-icon size="20" class="me-2">
                    {{ paymentMethod === 'gcash' ? 'mdi-cellphone' : 'mdi-credit-card' }}
                  </v-icon>
                  {{ paymentMethod === 'gcash' ? 'GCash' : 'Credit/Debit Card' }}
                </p>
              </div>
            </v-card-text>
          </v-card>
          <p class="text-body-2 text-medium-emphasis">
            By clicking "Subscribe Now", you agree to our terms and conditions. Your subscription will start immediately.
          </p>
        </div>

        <!-- Step 5: Processing / Success -->
        <div v-else-if="currentStep === 5" class="step-content text-center py-8">
          <div v-if="!subscriptionSuccess">
            <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
            <h4 class="text-h6 font-weight-bold">Processing Your Subscription</h4>
            <p class="text-body-2 text-medium-emphasis">Please wait while we complete your subscription...</p>
          </div>
          <div v-else>
            <v-icon color="success" size="80" class="mb-4">mdi-check-circle</v-icon>
            <h4 class="text-h5 font-weight-bold mb-2">Subscription Activated!</h4>
            <p class="text-body-2 text-medium-emphasis mb-6">
              Welcome to {{ initialPlan.name }}. Your subscription is now active.
            </p>
          </div>
        </div>
      </v-card-text>

      <!-- Footer with Navigation -->
      <v-divider />
      <v-card-actions class="pa-6">
        <v-btn
          v-if="currentStep > 1"
          variant="text"
          @click="previousStep"
        >
          Back
        </v-btn>
        <v-spacer />
        <v-btn
          v-if="currentStep < 5"
          color="primary"
          :disabled="!canProceed"
          @click="nextStep"
          elevation="2"
          rounded="lg"
        >
          {{ currentStep === 4 ? 'Subscribe Now' : 'Continue' }}
        </v-btn>
        <v-btn
          v-else-if="subscriptionSuccess"
          color="primary"
          @click="goToDashboard"
          elevation="2"
          rounded="lg"
        >
          Go to Dashboard
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from '@/lib/axios'
import { useNotification } from '@/composables/common/useNotification'
import type { Plan } from '@/types/billing'

interface Props {
  modelValue: boolean
  initialPlan: Plan
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const { showNotification } = useNotification()

const currentStep = ref(1)
const totalSteps = 4
const paymentMethod = ref<'gcash' | 'card' | null>(null)
const subscriptionSuccess = ref(false)

const cardForm = ref({
  name: '',
  number: '',
  expiry: '',
  cvc: '',
})

const cardErrors = ref({
  name: '',
  number: '',
  expiry: '',
  cvc: '',
  get any() {
    return this.name || this.number || this.expiry || this.cvc
  },
})

const canProceed = computed(() => {
  if (currentStep.value === 1) return true
  if (currentStep.value === 2) return !!paymentMethod.value
  if (currentStep.value === 3) {
    if (paymentMethod.value === 'gcash') return true
    return !cardErrors.value.any && cardForm.value.name && cardForm.value.number && cardForm.value.expiry && cardForm.value.cvc
  }
  if (currentStep.value === 4) return true
  return false
})

const formatCardNumber = () => {
  let value = cardForm.value.number.replace(/\s+/g, '')
  value = value.replace(/(\d{4})/g, '$1 ').trim()
  cardForm.value.number = value
}

const formatExpiry = () => {
  let value = cardForm.value.expiry.replace(/\D/g, '')
  if (value.length >= 2) {
    value = value.slice(0, 2) + '/' + value.slice(2, 4)
  }
  cardForm.value.expiry = value
}

const validateCardName = () => {
  if (!cardForm.value.name) {
    cardErrors.value.name = 'Cardholder name is required'
  } else if (cardForm.value.name.length < 3) {
    cardErrors.value.name = 'Name must be at least 3 characters'
  } else {
    cardErrors.value.name = ''
  }
}

const validateCardNumber = () => {
  const number = cardForm.value.number.replace(/\s+/g, '')
  if (!number) {
    cardErrors.value.number = 'Card number is required'
  } else if (number.length < 13 || number.length > 19) {
    cardErrors.value.number = 'Card number must be 13-19 digits'
  } else if (!/^\d+$/.test(number)) {
    cardErrors.value.number = 'Card number must contain only digits'
  } else {
    cardErrors.value.number = ''
  }
}

const validateExpiry = () => {
  if (!cardForm.value.expiry) {
    cardErrors.value.expiry = 'Expiry date is required'
  } else if (!/^\d{2}\/\d{2}$/.test(cardForm.value.expiry)) {
    cardErrors.value.expiry = 'Expiry must be MM/YY format'
  } else {
    cardErrors.value.expiry = ''
  }
}

const validateCVC = () => {
  if (!cardForm.value.cvc) {
    cardErrors.value.cvc = 'CVC is required'
  } else if (!/^\d{3,4}$/.test(cardForm.value.cvc)) {
    cardErrors.value.cvc = 'CVC must be 3-4 digits'
  } else {
    cardErrors.value.cvc = ''
  }
}

const nextStep = async () => {
  if (currentStep.value === 3 && paymentMethod.value === 'card') {
    validateCardName()
    validateCardNumber()
    validateExpiry()
    validateCVC()
    if (cardErrors.value.any) return
  }

  if (currentStep.value === 4) {
    await subscribe()
    return
  }

  currentStep.value++
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const subscribe = async () => {
  currentStep.value = 5
  subscriptionSuccess.value = false

  try {
    const payload: Record<string, unknown> = {
      plan_uuid: props.initialPlan.uuid,
      payment_method: paymentMethod.value,
    }

    if (paymentMethod.value === 'card') {
      payload.card = {
        name: cardForm.value.name,
        number: cardForm.value.number.replace(/\s+/g, ''),
        expiry: cardForm.value.expiry,
        cvc: cardForm.value.cvc,
      }
    }

    await axios.post('/billing/subscribe', payload)

    subscriptionSuccess.value = true
    emit('success')
    showNotification('Subscription activated successfully!', 'success')
  } catch (error: unknown) {
    subscriptionSuccess.value = false
    const message = extractErrorMessage(error)
    showNotification(message, 'error')
    currentStep.value = 4
  }
}

const extractErrorMessage = (err: unknown): string => {
  if (
    typeof err === 'object' &&
    err !== null &&
    'response' in err &&
    typeof (err as { response?: unknown }).response === 'object' &&
    (err as { response?: { data?: unknown } }).response !== null
  ) {
    const response = (err as { response?: { data?: unknown } }).response
    if (
      response &&
      typeof response === 'object' &&
      'data' in response &&
      typeof (response as { data?: unknown }).data === 'object' &&
      (response as { data?: { message?: unknown } }).data !== null
    ) {
      const data = (response as { data?: { message?: unknown } }).data
      if (data && typeof data === 'object' && 'message' in data && typeof data.message === 'string') {
        return data.message
      }
    }
  }
  return 'Failed to complete subscription. Please try again.'
}

const closeWizard = () => {
  emit('update:modelValue', false)
}

const goToDashboard = () => {
  closeWizard()
  window.location.href = '/dashboard'
}

const resetWizard = () => {
  currentStep.value = 1
  paymentMethod.value = null
  subscriptionSuccess.value = false
  cardForm.value = {
    name: '',
    number: '',
    expiry: '',
    cvc: '',
  }
  cardErrors.value = {
    name: '',
    number: '',
    expiry: '',
    cvc: '',
    get any() {
      return this.name || this.number || this.expiry || this.cvc
    },
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetWizard()
    }
  }
)
</script>

<style scoped>
.wizard-card {
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.28);
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.wizard-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-primary), 0.02) 100%);
  border-bottom: 1px solid rgb(var(--v-theme-surface-variant));
}

.plan-summary {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.06) 0%, rgba(var(--v-theme-primary), 0.03) 100%);
  border-bottom: 1px solid rgba(var(--v-theme-primary), 0.12);
}

.step-content {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.payment-radio {
  padding: 0.75rem;
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

.card-form {
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.review-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.max-w-400 {
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.security-hint {
  background: rgba(var(--v-theme-primary), 0.05);
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
  border-radius: 10px;
  padding: 10px 12px;
}
</style>
