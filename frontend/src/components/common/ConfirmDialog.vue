<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="400px"
    persistent
  >
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon
          :color="type === 'danger' ? 'error' : 'warning'"
          class="me-2"
          size="24"
        >
          {{ type === 'danger' ? 'mdi-alert-circle' : 'mdi-alert' }}
        </v-icon>
        <span class="text-h6">{{ title }}</span>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pt-4">
        <p class="text-body-1 mb-0">{{ message }}</p>
        <p v-if="warning" class="text-caption text-medium-emphasis mt-2 mb-0">
          {{ warning }}
        </p>
      </v-card-text>

      <v-card-actions class="px-4 pb-4">
        <v-spacer></v-spacer>
        <v-btn
          variant="text"
          class="text-none"
          @click="handleCancel"
          :disabled="loading"
        >
          {{ cancelText }}
        </v-btn>
        <v-btn
          :color="type === 'danger' ? 'error' : 'primary'"
          variant="flat"
          class="text-none"
          @click="handleConfirm"
          :loading="loading"
        >
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
interface Props {
  modelValue: boolean
  title?: string
  message: string
  warning?: string
  confirmText?: string
  cancelText?: string
  type?: 'danger' | 'warning'
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  title: 'Confirm Action',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  type: 'danger',
  loading: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  confirm: []
  cancel: []
}>()

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>
