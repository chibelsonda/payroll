<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <div class="d-flex align-center">
                <v-icon class="me-2" color="primary">mdi-clock-settings-outline</v-icon>
                <h1 class="text-h6 font-weight-bold mb-0">Attendance Settings</h1>
              </div>
              <v-chip
                v-if="settingsData?.is_default"
                color="info"
                size="small"
                variant="tonal"
                prepend-icon="mdi-information"
              >
                Using Defaults
              </v-chip>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading attendance settings...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              rounded="lg"
              density="compact"
              class="mb-4"
            >
              <div class="font-weight-medium">Failed to load settings</div>
              <div class="text-caption">{{ error.message }}</div>
            </v-alert>

            <!-- Settings Form -->
            <v-form v-else ref="formRef" @submit.prevent="handleSubmit">
              <!-- Company Selection -->
              <div class="mb-6">
                <div class="text-body-2 mb-2 font-weight-medium">Company</div>
                <v-select
                  v-model="selectedCompanyUuid"
                  :items="companies"
                  item-value="uuid"
                  item-title="name"
                  placeholder="Select company"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-office-building"
                  hide-details="auto"
                  @update:model-value="loadSettings"
                ></v-select>
              </div>

              <v-divider class="my-6"></v-divider>

              <!-- Shift Times Section -->
              <div class="mb-6">
                <h3 class="text-subtitle-1 font-weight-bold mb-4">Shift Times</h3>
                <v-row>
                  <v-col cols="12" sm="6" md="3">
                    <v-text-field
                      v-model="formData.default_shift_start"
                      type="time"
                      label="Shift Start"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-clock-start"
                      hide-details="auto"
                      :rules="[v => !!v || 'Shift start time is required']"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-text-field
                      v-model="formData.default_shift_end"
                      type="time"
                      label="Shift End"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-clock-end"
                      hide-details="auto"
                      :rules="[v => !!v || 'Shift end time is required']"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-text-field
                      v-model="formData.default_break_start"
                      type="time"
                      label="Break Start"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-coffee"
                      hide-details="auto"
                      :rules="[v => !!v || 'Break start time is required']"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6" md="3">
                    <v-text-field
                      v-model="formData.default_break_end"
                      type="time"
                      label="Break End"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-coffee-outline"
                      hide-details="auto"
                      :rules="[v => !!v || 'Break end time is required']"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </div>

              <v-divider class="my-6"></v-divider>

              <!-- Shift Configuration -->
              <div class="mb-6">
                <h3 class="text-subtitle-1 font-weight-bold mb-4">Shift Configuration</h3>
                <v-row>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model.number="formData.max_shift_hours"
                      type="number"
                      label="Max Shift Hours"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-timer"
                      min="1"
                      max="24"
                      hide-details="auto"
                      :rules="[
                        v => !!v || 'Max shift hours is required',
                        v => (v >= 1 && v <= 24) || 'Must be between 1 and 24 hours'
                      ]"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </div>

              <v-divider class="my-6"></v-divider>

              <!-- Auto-Correction Settings -->
              <div class="mb-6">
                <h3 class="text-subtitle-1 font-weight-bold mb-4">Auto-Correction Settings</h3>

                <!-- Master Switch -->
                <v-card variant="outlined" class="mb-4">
                  <v-card-text>
                    <div class="d-flex align-center justify-space-between">
                      <div class="flex-grow-1">
                        <div class="text-body-1 font-weight-medium mb-1">Enable Auto-Correction</div>
                        <div class="text-caption text-medium-emphasis">
                          Master switch for all auto-correction features. When disabled, missing logs will be flagged for manual review.
                        </div>
                      </div>
                      <v-switch
                        v-model="formData.enable_auto_correction"
                        color="primary"
                        hide-details
                      ></v-switch>
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Sub-settings (only enabled when master switch is on) -->
                <v-card variant="outlined" :class="{ 'opacity-50': !formData.enable_auto_correction }">
                  <v-card-text>
                    <div class="mb-4">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div>
                          <div class="text-body-2 font-weight-medium">Auto-Close Missing Time Out</div>
                          <div class="text-caption text-medium-emphasis">
                            Automatically close open IN logs at shift end when OUT is missing
                          </div>
                        </div>
                        <v-switch
                          v-model="formData.auto_close_missing_out"
                          color="primary"
                          hide-details
                          :disabled="!formData.enable_auto_correction"
                        ></v-switch>
                      </div>
                    </div>

                    <v-divider class="my-4"></v-divider>

                    <div>
                      <div class="d-flex align-center justify-space-between">
                        <div>
                          <div class="text-body-2 font-weight-medium">Auto-Deduct Break Time</div>
                          <div class="text-caption text-medium-emphasis">
                            Automatically deduct break duration for full-day shifts
                          </div>
                        </div>
                        <v-switch
                          v-model="formData.auto_deduct_break"
                          color="primary"
                          hide-details
                          :disabled="!formData.enable_auto_correction"
                        ></v-switch>
                      </div>
                    </div>
                  </v-card-text>
                </v-card>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex justify-end gap-2 mt-6">
                <v-btn
                  variant="outlined"
                  @click="resetForm"
                  :disabled="isSubmitting"
                >
                  Reset
                </v-btn>
                <v-btn
                  type="submit"
                  color="primary"
                  variant="flat"
                  :loading="isSubmitting"
                  :disabled="isSubmitting || !selectedCompanyUuid"
                >
                  Save Settings
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAttendanceSettings, useUpdateAttendanceSettings, type AttendanceSettings, type UpdateAttendanceSettingsData } from '@/composables'
import { useCompanies } from '@/composables'
import { useNotification } from '@/composables'
import { useZodForm } from '@/composables'
import { z } from 'zod'

const { showNotification } = useNotification()
const { data: companiesData } = useCompanies()
const formRef = ref()

const selectedCompanyUuid = ref<string | null>(null)
const companies = computed(() => {
  if (!companiesData.value) return []
  return companiesData.value.map(company => ({
    uuid: company.uuid,
    name: company.name,
  }))
})

// Computed for company UUID to ensure it's always a string or undefined
const companyUuidForQuery = computed(() => {
  const uuid = selectedCompanyUuid.value
  return typeof uuid === 'string' && uuid ? uuid : undefined
})

// Load settings when company is selected
const { data: settingsData, isLoading, error, refetch: refetchSettings } = useAttendanceSettings(
  companyUuidForQuery
)

// Initialize with first company if available
watch(companies, (newCompanies) => {
  if (newCompanies && newCompanies.length > 0 && !selectedCompanyUuid.value) {
    const firstCompany = newCompanies[0]
    if (firstCompany && firstCompany.uuid) {
      selectedCompanyUuid.value = firstCompany.uuid
    }
  }
}, { immediate: true })

const loadSettings = async () => {
  await refetchSettings()
}

// Form data
const formData = ref<UpdateAttendanceSettingsData>({
  company_uuid: '',
  default_shift_start: '08:00',
  default_break_start: '12:00',
  default_break_end: '13:00',
  default_shift_end: '17:00',
  max_shift_hours: 8,
  auto_close_missing_out: true,
  auto_deduct_break: true,
  enable_auto_correction: true,
})

// Update form data when settings are loaded
watch(settingsData, (newSettings) => {
  if (newSettings) {
    formData.value = {
      company_uuid: newSettings.company_uuid || selectedCompanyUuid.value || '',
      default_shift_start: formatTimeForInput(newSettings.default_shift_start),
      default_break_start: formatTimeForInput(newSettings.default_break_start),
      default_break_end: formatTimeForInput(newSettings.default_break_end),
      default_shift_end: formatTimeForInput(newSettings.default_shift_end),
      max_shift_hours: newSettings.max_shift_hours,
      auto_close_missing_out: newSettings.auto_close_missing_out,
      auto_deduct_break: newSettings.auto_deduct_break,
      enable_auto_correction: newSettings.enable_auto_correction,
    }
  }
}, { immediate: true })

// Format time from "HH:mm:ss" to "HH:mm" for time input
const formatTimeForInput = (time: string): string => {
  if (!time) return ''
  // Handle both "HH:mm:ss" and "HH:mm" formats
  return time.substring(0, 5)
}

// Format time from "HH:mm" to "HH:mm:ss" for backend
const formatTimeForBackend = (time: string): string => {
  if (!time) return '00:00:00'
  // If already in HH:mm:ss format, return as-is
  if (time.length === 8) return time
  // Otherwise, append :00 for seconds
  return `${time}:00`
}

const updateMutation = useUpdateAttendanceSettings()
const isSubmitting = computed(() => updateMutation.isPending.value)

const handleSubmit = async () => {
  if (!selectedCompanyUuid.value) {
    showNotification('Please select a company', 'error')
    return
  }

  try {
    const submitData: UpdateAttendanceSettingsData = {
      company_uuid: selectedCompanyUuid.value,
      default_shift_start: formatTimeForBackend(formData.value.default_shift_start),
      default_break_start: formatTimeForBackend(formData.value.default_break_start),
      default_break_end: formatTimeForBackend(formData.value.default_break_end),
      default_shift_end: formatTimeForBackend(formData.value.default_shift_end),
      max_shift_hours: formData.value.max_shift_hours,
      auto_close_missing_out: formData.value.auto_close_missing_out,
      auto_deduct_break: formData.value.auto_deduct_break,
      enable_auto_correction: formData.value.enable_auto_correction,
    }

    await updateMutation.mutateAsync(submitData)
    showNotification('Attendance settings saved successfully', 'success')
    await refetchSettings()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to save settings', 'error')
  }
}

const resetForm = () => {
  if (settingsData.value) {
    formData.value = {
      company_uuid: settingsData.value.company_uuid || selectedCompanyUuid.value || '',
      default_shift_start: formatTimeForInput(settingsData.value.default_shift_start),
      default_break_start: formatTimeForInput(settingsData.value.default_break_start),
      default_break_end: formatTimeForInput(settingsData.value.default_break_end),
      default_shift_end: formatTimeForInput(settingsData.value.default_shift_end),
      max_shift_hours: settingsData.value.max_shift_hours,
      auto_close_missing_out: settingsData.value.auto_close_missing_out,
      auto_deduct_break: settingsData.value.auto_deduct_break,
      enable_auto_correction: settingsData.value.enable_auto_correction,
    }
  }
}
</script>

<style scoped>
.opacity-50 {
  opacity: 0.5;
  pointer-events: none;
}

.gap-2 {
  gap: 8px;
}
</style>
