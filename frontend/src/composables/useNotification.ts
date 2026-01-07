import { ref } from 'vue'

interface NotificationState {
  show: boolean
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
}

const state = ref<NotificationState>({
  show: false,
  message: '',
  type: 'success'
})

export const useNotification = () => {
  const showNotification = (message: string, type: NotificationState['type'] = 'success') => {
    state.value = {
      show: true,
      message,
      type
    }
  }

  const showSuccess = (message: string) => {
    showNotification(message, 'success')
  }

  const showError = (message: string) => {
    showNotification(message, 'error')
  }

  const showWarning = (message: string) => {
    showNotification(message, 'warning')
  }

  const showInfo = (message: string) => {
    showNotification(message, 'info')
  }

  const hideNotification = () => {
    state.value.show = false
  }

  return {
    state,
    showNotification,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    hideNotification
  }
}
