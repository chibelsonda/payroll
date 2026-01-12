<template>
  <v-card elevation="2" rounded="lg" class="attendance-logger-card">
    <v-card-title class="px-4 py-3">
      <div class="d-flex align-center">
        <v-icon class="me-2" color="primary">mdi-clock-outline</v-icon>
        <h2 class="text-h6 font-weight-bold mb-0">Time Clock</h2>
      </div>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-6">
      <!-- Current Status -->
      <div class="text-center mb-6">
        <v-chip
          :color="currentStatusColor"
          size="large"
          class="mb-3"
          prepend-icon="mdi-account-clock"
        >
          {{ currentStatusText }}
        </v-chip>
        <div class="text-body-2 text-medium-emphasis mt-2">
          {{ currentTimeText }}
        </div>
      </div>

      <!-- Auto-correction Warning -->
      <v-alert
        v-if="showAutoCorrectionWarning"
        type="warning"
        variant="tonal"
        rounded="lg"
        density="compact"
        class="mb-4"
        closable
        @click:close="showAutoCorrectionWarning = false"
      >
        <div class="font-weight-medium">Previous time-out was missing</div>
        <div class="text-caption mt-1">System auto-corrected the attendance log.</div>
      </v-alert>

      <!-- Action Button -->
      <div class="text-center">
        <v-btn
          :color="buttonColor"
          :prepend-icon="buttonIcon"
          size="x-large"
          variant="elevated"
          :loading="isSubmitting"
          :disabled="isSubmitting || !attendanceStore.lastLogType"
          @click="handleToggle"
          class="px-8"
        >
          {{ buttonText }}
        </v-btn>
      </div>

      <!-- Loading State -->
      <div v-if="attendanceStore.loading && !isSubmitting" class="text-center mt-4">
        <v-progress-circular indeterminate color="primary" size="24"></v-progress-circular>
        <p class="text-caption text-medium-emphasis mt-2">Checking current status...</p>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAttendanceStore } from '@/stores/attendance'
import { useNotification } from '@/composables/useNotification'

const attendanceStore = useAttendanceStore()
const { showNotification, showWarning } = useNotification()
const showAutoCorrectionWarning = ref(false)

// Get last log from store
const lastLog = computed(() => {
  const logs = attendanceStore.todayLogs
  return logs[logs.length - 1]
})

// Determine button state based on last log type
const currentStatus = computed<'IN' | 'OUT'>(() => attendanceStore.lastLogType)

const currentStatusText = computed(() => {
  return currentStatus.value === 'IN' ? 'Clocked In' : 'Clocked Out'
})

const currentStatusColor = computed(() => {
  return currentStatus.value === 'IN' ? 'success' : 'default'
})

const currentTimeText = computed(() => {
  if (!lastLog.value) return 'No time logs today'
  const time = new Date(lastLog.value.log_time).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
  return `Last ${lastLog.value.type} at ${time}`
})

const buttonText = computed(() => {
  return currentStatus.value === 'IN' ? 'Time Out' : 'Time In'
})

const buttonIcon = computed(() => {
  return currentStatus.value === 'IN' ? 'mdi-logout' : 'mdi-login'
})

const buttonColor = computed(() => {
  return currentStatus.value === 'IN' ? 'error' : 'primary'
})

const isSubmitting = computed(() => attendanceStore.loading)

// Check for auto-corrections in recent logs (from backend)
watch(() => attendanceStore.logs, (newLogs) => {
  const today = new Date().toISOString().split('T')[0]
  const recentAutoCorrected = newLogs
    .filter(log => log.log_time.startsWith(today))
    .filter(log => log.is_auto_corrected)
    .sort((a, b) => new Date(b.log_time).getTime() - new Date(a.log_time).getTime())[0]

  if (recentAutoCorrected) {
    showAutoCorrectionWarning.value = true
    showWarning('Previous time-out was missing. System auto-corrected.')
  }
}, { immediate: true })

const handleToggle = async () => {
  try {
    // Determine next type based on current status
    const nextType: 'IN' | 'OUT' = currentStatus.value === 'IN' ? 'OUT' : 'IN'
    
    // Submit log to backend (backend will process and recalculate)
    await attendanceStore.submitLog(nextType)

    showNotification(`Successfully clocked ${nextType.toLowerCase()}`, 'success')
    
    // Refresh logs and summary from backend
    await attendanceStore.refreshAll()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to record attendance'
    showNotification(message, 'error')
  }
}

onMounted(() => {
  attendanceStore.refreshAll()
})
</script>

<style scoped>
.attendance-logger-card {
  max-width: 500px;
  margin: 0 auto;
}
</style>
