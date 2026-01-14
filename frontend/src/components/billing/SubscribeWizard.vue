<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    max-width="760"
    transition="fade-transition"
    scrim="rgba(0,0,0,0.8)"
    close-on-esc
  >
    <v-card class="wizard-shell" rounded="xl">
      <!-- Header -->
      <div class="wizard-hero">
        <div>
          <div class="text-overline text-medium-emphasis mb-1">Subscription wizard</div>
          <h2 class="text-h5 font-weight-bold mb-1">Complete your subscription</h2>
          <div class="text-caption text-medium-emphasis d-flex align-center gap-2">
            <v-icon size="18" color="primary">mdi-lock</v-icon>
            Secure checkout powered by your chosen method
          </div>
        </div>
        <v-btn icon="mdi-close" variant="text" color="white" @click="closeWizard" />
      </div>

      <!-- Inline plan strip -->
      <div class="plan-strip">
        <v-avatar color="primary" size="44">
          <v-icon color="white">mdi-package-variant</v-icon>
        </v-avatar>
        <div class="flex-grow-1">
          <div class="text-body-1 font-weight-bold">{{ initialPlan.name }}</div>
          <div class="text-caption text-medium-emphasis">
            ₱{{ initialPlan.price.toLocaleString() }} / {{ initialPlan.billing_cycle }} • up to
            {{ initialPlan.max_employees }} employees
          </div>
        </div>
        <v-chip size="small" color="primary" variant="elevated" prepend-icon="mdi-shield-check">Secure</v-chip>
      </div>

      <!-- Stepper -->
      <v-stepper v-model="currentStep" class="px-6 pt-4" hide-actions density="comfortable">
        <v-stepper-header>
          <v-stepper-item
            v-for="step in totalSteps"
            :key="step"
            :step="step"
            :complete="currentStep > step"
            :color="currentStep >= step ? 'primary' : 'grey-lighten-2'"
            :title="stepLabels[step - 1]"
          />
        </v-stepper-header>
      </v-stepper>

      <!-- Content -->
      <v-card-text class="px-6 py-6">
        <!-- Step 1 -->
        <div v-if="currentStep === 1" class="step fade">
          <h3 class="text-h6 font-weight-bold mb-4">Confirm your plan</h3>
          <v-card variant="outlined" rounded="lg">
            <v-card-text class="pa-5 d-flex align-center justify-space-between">
              <div class="d-flex align-center gap-3">
                <v-avatar color="primary" size="40">
                  <v-icon color="white">mdi-rocket-launch</v-icon>
                </v-avatar>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">{{ initialPlan.name }}</div>
                  <div class="text-caption text-medium-emphasis">Up to {{ initialPlan.max_employees }} employees</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-h6 font-weight-bold primary--text">₱{{ initialPlan.price.toLocaleString() }}</div>
                <div class="text-caption text-medium-emphasis text-right">/ {{ initialPlan.billing_cycle }}</div>
              </div>
            </v-card-text>
          </v-card>
          <p class="text-body-2 text-medium-emphasis mt-4">
            You can change your plan anytime. Continue to choose your payment method.
          </p>
        </div>

        <!-- Step 2 -->
        <div v-else-if="currentStep === 2" class="step fade">
          <h3 class="text-h6 font-weight-bold mb-4">Choose payment method</h3>
          <v-radio-group v-model="paymentMethod">
            <v-radio
              value="gcash"
              class="method-tile mb-3"
              color="primary"
            >
              <template #label>
                <div class="d-flex align-center w-100">
                  <v-avatar color="success" size="46" class="me-3">
                    <v-icon color="white">mdi-cellphone</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="text-subtitle-2 font-weight-bold">GCash</div>
                    <div class="text-caption text-medium-emphasis">Fast and secure wallet checkout</div>
                  </div>
                  <v-chip size="x-small" color="success" variant="tonal">Recommended</v-chip>
                </div>
              </template>
            </v-radio>
            <v-radio
              value="card"
              class="method-tile"
              color="primary"
            >
              <template #label>
                <div class="d-flex align-center w-100">
                  <v-avatar color="info" size="46" class="me-3">
                    <v-icon color="white">mdi-credit-card-outline</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="text-subtitle-2 font-weight-bold">Credit / Debit Card</div>
                    <div class="text-caption text-medium-emphasis">Visa, Mastercard, and more</div>
                  </div>
                </div>
              </template>
            </v-radio>
          </v-radio-group>
        </div>

        <!-- Step 3 -->
        <div v-else-if="currentStep === 3" class="step fade">
          <h3 class="text-h6 font-weight-bold mb-4">Payment details</h3>
          <div v-if="paymentMethod === 'gcash'" class="text-center py-8">
            <v-icon size="70" color="success" class="mb-4">mdi-check-decagram</v-icon>
            <h4 class="text-h6 font-weight-bold mb-2">GCash selected</h4>
            <p class="text-body-2 text-medium-emphasis max-w-420 mx-auto">
              You will be securely redirected to GCash to complete the payment after confirming.
            </p>
          </div>
          <div v-else class="card-form">
            <v-text-field
              v-model="cardForm.name"
              label="Cardholder name"
              placeholder="Juan Dela Cruz"
              variant="outlined"
              class="mb-3"
              @blur="validateCardName"
              hide-details
              prepend-inner-icon="mdi-account"
            />
            <v-text-field
              v-model="cardForm.number"
              label="Card number"
              placeholder="4111 1111 1111 1111"
              variant="outlined"
              maxlength="19"
              @input="formatCardNumber"
              @blur="validateCardNumber"
              class="mb-3"
              hide-details
              prepend-inner-icon="mdi-credit-card-outline"
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
                  prepend-inner-icon="mdi-calendar"
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
                  prepend-inner-icon="mdi-shield-lock-outline"
                />
              </v-col>
            </v-row>

            <v-alert v-if="cardErrors.any" type="error" variant="tonal" class="mt-3">
              <div v-if="cardErrors.name" class="text-caption mb-1">{{ cardErrors.name }}</div>
              <div v-if="cardErrors.number" class="text-caption mb-1">{{ cardErrors.number }}</div>
              <div v-if="cardErrors.expiry" class="text-caption mb-1">{{ cardErrors.expiry }}</div>
              <div v-if="cardErrors.cvc" class="text-caption">{{ cardErrors.cvc }}</div>
            </v-alert>

            <div class="security-note mt-3 d-flex align-center gap-2">
              <v-icon size="18" color="primary">mdi-lock-outline</v-icon>
              <span class="text-caption text-medium-emphasis">Payments are encrypted and never stored.</span>
            </div>
          </div>
        </div>

        <!-- Step 4 -->
        <div v-else-if="currentStep === 4" class="step fade">
          <h3 class="text-h6 font-weight-bold mb-4">Review & confirm</h3>
          <v-card variant="outlined" rounded="lg" class="mb-5">
            <v-card-text class="pa-5">
              <div class="review-row">
                <span class="text-medium-emphasis">Plan</span>
                <span class="font-weight-bold">{{ initialPlan.name }}</span>
              </div>
              <v-divider class="my-3" />
              <div class="review-row">
                <span class="text-medium-emphasis">Price</span>
                <span class="text-h6 font-weight-bold primary--text">₱{{ initialPlan.price.toLocaleString() }}</span>
              </div>
              <v-divider class="my-3" />
              <div class="review-row">
                <span class="text-medium-emphasis">Payment method</span>
                <span class="font-weight-bold">
                  <v-icon size="18" class="me-1">
                    {{ paymentMethod === 'gcash' ? 'mdi-cellphone' : 'mdi-credit-card' }}
                  </v-icon>
                  {{ paymentMethod === 'gcash' ? 'GCash' : 'Credit/Debit Card' }}
                </span>
              </div>
            </v-card-text>
          </v-card>
          <p class="text-body-2 text-medium-emphasis">
            By clicking “Subscribe Now”, your subscription will start immediately.
          </p>
        </div>

        <!-- Step 5 -->
        <div v-else class="step fade text-center py-8">
          <div v-if="!subscriptionSuccess">
            <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
            <h4 class="text-h6 font-weight-bold mb-1">Processing your subscription</h4>
            <p class="text-body-2 text-medium-emphasis">Please wait while we complete your subscription...</p>
          </div>
          <div v-else>
            <v-icon color="success" size="82" class="mb-4">mdi-check-decagram</v-icon>
            <h4 class="text-h5 font-weight-bold mb-2">Subscription activated!</h4>
            <p class="text-body-2 text-medium-emphasis mb-6">
              Welcome to {{ initialPlan.name }}. Your subscription is now active.
            </p>
          </div>
        </div>
      </v-card-text>

      <!-- Footer -->
      <v-divider />
      <v-card-actions class="pa-5">
        <v-btn v-if="currentStep > 1 && currentStep < 5" variant="text" @click="previousStep">Back</v-btn>
        <v-spacer />
        <v-btn
          v-if="currentStep < 5"
          color="primary"
          rounded="lg"
          :disabled="!canProceed"
          @click="nextStep"
        >
          {{ currentStep === 4 ? 'Subscribe Now' : 'Continue' }}
        </v-btn>
        <v-btn
          v-else-if="subscriptionSuccess"
          color="primary"
          rounded="lg"
          @click="goToDashboard"
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
const stepLabels = ['Confirm plan', 'Payment method', 'Payment details', 'Review']
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
    return (
      !cardErrors.value.any &&
      cardForm.value.name &&
      cardForm.value.number &&
      cardForm.value.expiry &&
      cardForm.value.cvc
    )
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
  if (currentStep.value > 1) currentStep.value--
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
    if (open) resetWizard()
  }
)
</script>

<style scoped>
.wizard-shell {
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.04);
  overflow: hidden;
}

.wizard-hero {
  background: radial-gradient(circle at 20% 20%, rgba(var(--v-theme-primary), 0.18), transparent 50%),
    radial-gradient(circle at 80% 0%, rgba(var(--v-theme-primary), 0.14), transparent 45%),
    linear-gradient(135deg, rgba(var(--v-theme-primary), 0.18) 0%, rgba(var(--v-theme-primary), 0.08) 100%);
  color: white;
  padding: 20px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.plan-strip {
  padding: 12px 24px;
  background: rgba(var(--v-theme-primary), 0.06);
  border-bottom: 1px solid rgba(var(--v-theme-primary), 0.12);
  display: flex;
  align-items: center;
  gap: 12px;
}

.step {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.method-tile {
  border: 1px solid rgb(var(--v-theme-surface-variant));
  border-radius: 12px;
  padding: 12px 10px;
  transition: all 0.2s;
}

.method-tile:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.method-tile.v-radio--is-selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}

.card-form {
  border: 1px dashed rgba(var(--v-theme-primary), 0.2);
  border-radius: 12px;
  padding: 16px;
  background: rgba(var(--v-theme-primary), 0.02);
}

.review-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.security-note {
  background: rgba(var(--v-theme-primary), 0.06);
  border: 1px solid rgba(var(--v-theme-primary), 0.14);
  border-radius: 10px;
  padding: 10px 12px;
}

.max-w-420 {
  max-width: 420px;
}

/* Hide scrollbars on the stepper header (no horizontal scroll indicator) */
:deep(.v-stepper-header) {
  overflow: visible;
}
:deep(.v-stepper-header::-webkit-scrollbar) {
  display: none;
}
:deep(.v-stepper-header) {
  scrollbar-width: none;
}
</style>
