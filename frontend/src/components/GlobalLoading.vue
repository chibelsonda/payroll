<template>
  <v-progress-linear
    v-if="loadingStore.isLoading || progress > 0"
    :model-value="progress"
    color="primary"
    height="4"
    class="global-loading-bar"
    :indeterminate="false"
  ></v-progress-linear>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { useLoadingStore } from '@/stores/loading'

const loadingStore = useLoadingStore()
const progress = ref(0)
let intervalId: ReturnType<typeof setInterval> | null = null
let finishTimeoutId: ReturnType<typeof setTimeout> | null = null

watch(
  () => loadingStore.isLoading,
  (isLoading) => {
    if (isLoading) {
      // Start animation from 0
      progress.value = 0

      // Clear any existing intervals/timeouts
      if (intervalId) clearInterval(intervalId)
      if (finishTimeoutId) clearTimeout(finishTimeoutId)

      // Animate progress from 0 to 90%
      intervalId = setInterval(() => {
        if (progress.value < 90) {
          progress.value += 2
        } else {
          if (intervalId) clearInterval(intervalId)
        }
      }, 50)
    } else {
      // Loading completed - finish to 100% then reset
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }
      progress.value = 100
      finishTimeoutId = setTimeout(() => {
        progress.value = 0
      }, 300)
    }
  }
)

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  if (finishTimeoutId) clearTimeout(finishTimeoutId)
})
</script>

<style scoped>
.global-loading-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 2000;
  margin: 0;
  width: 100%;
}
</style>
