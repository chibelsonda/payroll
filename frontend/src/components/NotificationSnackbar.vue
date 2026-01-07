<template>
  <v-snackbar
    v-model="notification.state.value.show"
    :color="notification.state.value.type"
    :timeout="4000"
    location="top right"
    @update:model-value="onUpdate"
  >
    {{ notification.state.value.message }}
    <template #actions>
      <v-btn
        variant="text"
        icon="mdi-close"
        @click="notification.hideNotification()"
      ></v-btn>
    </template>
  </v-snackbar>
</template>

<script setup lang="ts">
import { useNotification } from '@/composables/useNotification'

const notification = useNotification()

const onUpdate = (value: boolean) => {
  if (!value) {
    notification.hideNotification()
  }
}
</script>
