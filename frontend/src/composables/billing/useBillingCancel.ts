import { useMutation } from '@tanstack/vue-query'
import billingService from '@/services/billingService'
import type { Payment } from '@/types/billing'

export const useBillingCancel = () => {
  return useMutation<Payment, unknown, string>({
    mutationKey: ['billing', 'cancel'],
    mutationFn: async (paymentIntentId: string) => {
      return billingService.cancelPayment(paymentIntentId)
    },
  })
}
