<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <v-row>
      <v-col cols="12">
        <div class="text-h4 font-weight-bold mb-2">My Profile</div>
        <div class="text-body-1 text-medium-emphasis mb-6">
          View and manage your profile information.
        </div>
      </v-col>
    </v-row>

    <!-- Profile Content -->
    <v-row>
      <v-col cols="12" md="8">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-account</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Profile Information</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <v-list density="comfortable" class="pa-0">
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Full Name</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ fullName }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-4"></v-divider>
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Email Address</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ auth.user?.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider v-if="auth.user?.employee" class="my-4"></v-divider>
              <v-list-item v-if="auth.user?.employee" class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Employee ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.employee_no || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider v-if="auth.user?.employee?.department" class="my-4"></v-divider>
              <v-list-item v-if="auth.user?.employee?.department" class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Department</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.department?.name || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider v-if="auth.user?.employee?.position" class="my-4"></v-divider>
              <v-list-item v-if="auth.user?.employee?.position" class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Position</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.position?.title || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Profile Picture and Account Actions -->
      <v-col cols="12" md="4">
        <!-- Profile Picture Card -->
        <v-card elevation="2" rounded="lg" class="mb-4">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-image</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Profile Picture</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <div class="text-center">
              <!-- Avatar Display -->
              <div class="avatar-container mb-6">
                <v-avatar size="140" class="avatar-shadow">
                  <v-img
                    v-if="avatarPreview || auth.user?.avatar_url"
                    :src="avatarPreview || auth.user?.avatar_url"
                    cover
                    alt="Profile Picture"
                  ></v-img>
                  <v-icon v-else size="72" color="grey-lighten-1">mdi-account-circle</v-icon>
                </v-avatar>
              </div>

              <!-- File Input (Hidden) -->
              <input
                ref="fileInputRef"
                type="file"
                accept="image/*"
                style="display: none"
                @change="handleFileSelect"
              />

              <!-- Action Buttons -->
              <div class="d-flex flex-column">
                <!-- Change Photo Button -->
                <v-btn
                  v-if="!selectedFile"
                  color="primary"
                  variant="flat"
                  prepend-icon="mdi-camera"
                  @click="fileInputRef?.click()"
                  :disabled="isUploading"
                  block
                >
                  Change Photo
                </v-btn>

                <!-- Upload and Cancel Buttons (shown when file is selected) -->
                <template v-if="selectedFile">
                  <v-btn
                    color="success"
                    variant="flat"
                    prepend-icon="mdi-upload"
                    :loading="isUploading"
                    :disabled="isUploading"
                    @click="handleUpload"
                    block
                    size="large"
                    class="mb-2"
                  >
                    Upload
                  </v-btn>
                  <v-btn
                    color="default"
                    variant="outlined"
                    @click="handleCancel"
                    :disabled="isUploading"
                    block
                  >
                    Cancel
                  </v-btn>
                </template>
              </div>

              <!-- Error Message -->
              <v-alert
                v-if="uploadError"
                type="error"
                variant="tonal"
                density="compact"
                rounded="lg"
                class="mt-4"
                closable
                @click:close="uploadError = null"
              >
                {{ uploadError }}
              </v-alert>

              <!-- File Info (shown when file is selected) -->
              <div v-if="selectedFile && !isUploading" class="mt-4">
                <v-chip
                  color="info"
                  variant="tonal"
                  size="small"
                  prepend-icon="mdi-file-image"
                >
                  {{ selectedFile.name }}
                </v-chip>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Account Actions Card -->
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-cog</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Account</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-alert
              type="info"
              variant="tonal"
              density="compact"
              rounded="lg"
              class="mb-4"
            >
              <div class="text-caption">
                To update your profile information, please contact your administrator.
              </div>
            </v-alert>
          </v-card-text>
        </v-card>

        <!-- Quick Links -->
        <v-card elevation="2" rounded="lg" class="mt-4">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-link</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Quick Links</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-list density="compact" class="pa-0">
              <v-list-item
                :to="dashboardRoute"
                prepend-icon="mdi-view-dashboard"
                title="Dashboard"
                class="px-0"
              >
              </v-list-item>
              <v-divider v-if="showAttendanceLink" class="my-2"></v-divider>
              <v-list-item
                v-if="showAttendanceLink"
                :to="attendanceRoute"
                prepend-icon="mdi-calendar-clock"
                title="Attendance"
                class="px-0"
              >
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotification } from '@/composables'
import apiAxios, { webAxios } from '@/lib/axios'
import { useQueryClient } from '@tanstack/vue-query'
import type { User } from '@/types/user'

const auth = useAuthStore()
const notification = useNotification()
const queryClient = useQueryClient()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const isUploading = ref(false)
const uploadError = ref<string | null>(null)

const fullName = computed(() => {
  const user = auth.user
  if (!user) return 'Guest'
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`
  }
  return user.email
})

const dashboardRoute = computed(() => {
  const role = auth.user?.role
  if (role === 'owner') return '/owner'
  if (role === 'admin') return '/admin'
  return '/employee'
})

const attendanceRoute = computed(() => {
  const role = auth.user?.role
  if (role === 'owner') return '/owner/attendance'
  if (role === 'admin') return '/admin/attendance'
  return '/employee/attendance'
})

const showAttendanceLink = computed(() => {
  const role = auth.user?.role
  return role === 'employee' || role === 'hr' || role === 'payroll' || role === 'owner' || role === 'admin'
})

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) {
    return
  }

  // Validate file type
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Please select an image file'
    return
  }

  // Validate file size (2MB = 2 * 1024 * 1024 bytes)
  if (file.size > 2 * 1024 * 1024) {
    uploadError.value = 'Image size must be less than 2MB'
    return
  }

  uploadError.value = null
  selectedFile.value = file

  // Create preview URL
  avatarPreview.value = URL.createObjectURL(file)
}

const handleCancel = () => {
  selectedFile.value = null
  if (avatarPreview.value) {
    URL.revokeObjectURL(avatarPreview.value)
    avatarPreview.value = null
  }
  uploadError.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const handleUpload = async () => {
  if (!selectedFile.value) {
    return
  }

  isUploading.value = true
  uploadError.value = null

  try {
    // Get CSRF cookie first (required for Sanctum)
    await webAxios.get('/sanctum/csrf-cookie')

    // Create FormData
    const formData = new FormData()
    formData.append('avatar', selectedFile.value)

    // Upload avatar
    const response = await apiAxios.post<{ data: User }>('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    // Update user in store and cache
    const updatedUser = response.data.data
    queryClient.setQueryData(['user'], updatedUser)

    // Refresh user in auth store
    await auth.fetchUser()

    // Clean up preview URL
    if (avatarPreview.value) {
      URL.revokeObjectURL(avatarPreview.value)
      avatarPreview.value = null
    }

    // Reset file input
    selectedFile.value = null
    if (fileInputRef.value) {
      fileInputRef.value.value = ''
    }

    notification.showSuccess('Profile picture uploaded successfully!')
  } catch (error: unknown) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }

    if (apiError?.response?.data?.errors) {
      const errorMessages = Object.values(apiError.response.data.errors).flat()
      uploadError.value = errorMessages[0] || 'Failed to upload profile picture'
    } else {
      uploadError.value = apiError?.response?.data?.message || 'Failed to upload profile picture. Please try again.'
    }

    notification.showError(uploadError.value)
  } finally {
    isUploading.value = false
  }
}
</script>

<style scoped>
.avatar-container {
  display: flex;
  justify-content: center;
  align-items: center;
}

.avatar-shadow {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 4px solid rgb(var(--v-theme-surface));
  transition: transform 0.2s, box-shadow 0.2s;
}

.avatar-shadow:hover {
  transform: scale(1.02);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}
</style>
