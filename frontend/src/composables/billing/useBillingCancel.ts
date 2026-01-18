import { useMutation } from '@tanstack/vue-query'
import apiAxios from '@/lib/axios'
import type { Payment } from '@/types/billing'

const cancelPayment = async (paymentIntentId: string): Promise<Payment> => {
  const response = await apiAxios.get('/billing/cancel', {
    params: { payment_intent_id: paymentIntentId },
  })
  return response.data.data as Payment
}

export const useBillingCancel = () => {
  return useMutation<Payment, unknown, string>({
    mutationKey: ['billing', 'cancel'],
    mutationFn: cancelPayment,
  })
}
