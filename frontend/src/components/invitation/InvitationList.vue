<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Team Invitations</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-email-plus"
                @click="showInvitationForm = true"
              >
                Send Invitation
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading invitations...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="ma-4"
              rounded="lg"
              density="compact"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load invitations</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Invitations Table -->
            <v-data-table
              v-else
              :items="invitations"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="invitations-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="invitations.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-email-outline</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No invitations</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">
                      Start by sending an invitation to a team member
                    </p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-email-plus"
                      @click="showInvitationForm = true"
                    >
                      Send Invitation
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.email`]="{ item }">
                <div class="d-flex align-center">
                  <v-icon size="20" class="me-2">mdi-email</v-icon>
                  <span>{{ item.email }}</span>
                </div>
              </template>

              <template v-slot:[`item.role`]="{ item }">
                <v-chip
                  :color="getRoleColor(item.role)"
                  size="small"
                  variant="flat"
                  class="text-capitalize"
                >
                  {{ item.role }}
                </v-chip>
              </template>

              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  size="small"
                  variant="flat"
                  class="text-capitalize"
                >
                  {{ item.status }}
                </v-chip>
              </template>

              <template v-slot:[`item.expires_at`]="{ item }">
                <span v-if="item.expires_at" class="text-body-2">
                  {{ formatDate(item.expires_at) }}
                </span>
                <span v-else class="text-body-2 text-medium-emphasis">-</span>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <div class="d-flex align-center ga-2">
                  <v-btn
                    v-if="item.status === 'pending'"
                    icon="mdi-close"
                    size="small"
                    variant="text"
                    color="error"
                    @click="handleCancel(item)"
                    :loading="cancellingInvitation === item.uuid"
                  >
                  </v-btn>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Invitation Form Dialog -->
    <InvitationForm
      v-model="showInvitationForm"
      @success="handleInvitationSent"
    />

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model="showDeleteDialog"
      title="Cancel Invitation"
      :message="deleteMessage"
      warning="This action cannot be undone."
      confirm-text="Cancel"
      cancel-text="Keep"
      type="danger"
      :loading="cancellingInvitation !== null"
      @confirm="handleConfirmCancel"
    />
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useInvitations, useCancelInvitation } from '@/composables'
import type { Invitation } from '@/types/invitation'
import InvitationForm from './InvitationForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { formatDateForDisplay } from '@/lib/datetime'

const { data: invitations, isLoading, error, refetch } = useInvitations()
const cancelInvitationMutation = useCancelInvitation()
const showInvitationForm = ref(false)
const showDeleteDialog = ref(false)
const invitationToCancel = ref<Invitation | null>(null)
const cancellingInvitation = ref<string | null>(null)

const headers = [
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Role', key: 'role', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Expires At', key: 'expires_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '100px' },
]

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    owner: 'purple',
    admin: 'blue',
    hr: 'green',
    finance: 'orange',
    employee: 'grey',
  }
  return colors[role] || 'grey'
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'warning',
    accepted: 'success',
    expired: 'error',
    cancelled: 'grey',
  }
  return colors[status] || 'grey'
}

const formatDate = (dateString: string) => {
  try {
    return formatDateForDisplay(dateString)
  } catch {
    return dateString
  }
}

const deleteMessage = computed(() => {
  if (!invitationToCancel.value) return ''
  return `Are you sure you want to cancel the invitation to ${invitationToCancel.value.email}?`
})

const handleCancel = (invitation: Invitation) => {
  invitationToCancel.value = invitation
  showDeleteDialog.value = true
}

const handleConfirmCancel = async () => {
  if (!invitationToCancel.value) return

  cancellingInvitation.value = invitationToCancel.value.uuid
  try {
    await cancelInvitationMutation.mutateAsync(invitationToCancel.value.uuid)
    showDeleteDialog.value = false
    invitationToCancel.value = null
  } finally {
    cancellingInvitation.value = null
  }
}

const handleInvitationSent = () => {
  showInvitationForm.value = false
  refetch()
}
</script>

<style scoped>
.invitations-table :deep(.v-data-table__td) {
  padding: 8px 16px;
}
</style>
