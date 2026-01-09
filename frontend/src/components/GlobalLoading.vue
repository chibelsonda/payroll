<template>
  <v-overlay
    :model-value="loadingStore.isLoading"
    class="align-center justify-center"
    persistent
    z-index="9999"
  >
    <div class="text-center">
      <v-progress-circular
        indeterminate
        color="primary"
        size="64"
        width="6"
      ></v-progress-circular>
      <p class="mt-4 text-body-1 text-white">Loading...</p>
    </div>
  </v-overlay>
</template>

<script setup lang="ts">
import { useLoadingStore } from '@/stores/loading'

const loadingStore = useLoadingStore()
</script>

<style scoped>
.v-overlay {
  backdrop-filter: blur(2px);
}
</style>
