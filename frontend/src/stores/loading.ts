import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useLoadingStore = defineStore('loading', () => {
  const activeRequests = ref(0)

  const isLoading = computed(() => activeRequests.value > 0)

  const increment = () => {
    activeRequests.value++
  }

  const decrement = () => {
    if (activeRequests.value > 0) {
      activeRequests.value--
    }
  }

  return {
    isLoading,
    increment,
    decrement,
  }
})
