export interface Plan {
  uuid: string
  name: string
  price: number
  billing_cycle: 'monthly' | 'yearly'
  max_employees: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Subscription {
  uuid: string
  status: 'trialing' | 'pending' | 'active' | 'past_due' | 'canceled' | 'expired'
  starts_at?: string
  ends_at?: string
  trial_ends_at?: string
  plan?: Plan
  plan_uuid?: string
  company_uuid?: string
  is_active: boolean
  is_trialing: boolean
  created_at: string
  updated_at: string
}

export interface Payment {
  uuid: string
  provider: string
  method: 'gcash' | 'card' | 'maya' | string
  provider_reference_id?: string
  checkout_url?: string
  amount: number
  currency: string
  status: 'pending' | 'paid' | 'failed' | 'expired'
  paid_at?: string
  metadata?: Record<string, any>
  subscription?: Subscription
  subscription_uuid?: string
  company_uuid?: string
  is_paid: boolean
  is_pending: boolean
  created_at: string
  updated_at: string
}

export interface SubscribeRequest {
  plan_uuid: string
  payment_method: 'gcash' | 'card'
  provider?: string
  success_url?: string
  cancel_url?: string
}
