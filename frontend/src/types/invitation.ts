export interface Invitation {
  uuid: string
  email: string
  role: 'owner' | 'admin' | 'hr' | 'finance' | 'employee'
  status: 'pending' | 'accepted' | 'expired' | 'cancelled'
  expires_at?: string | null
  accepted_at?: string | null
  created_at?: string
  updated_at?: string
  company?: {
    uuid: string
    name: string
  }
  inviter?: {
    uuid: string
    name: string
    email: string
  }
}

export interface CreateInvitationData {
  email: string
  role: 'owner' | 'admin' | 'hr' | 'finance' | 'employee'
}

export interface AcceptInvitationData {
  token: string
}
