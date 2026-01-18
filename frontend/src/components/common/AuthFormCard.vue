<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>{{ title }}</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-alert v-if="error" type="error" class="mb-4" closable @click:close="$emit('clear-error')">
              {{ error }}
            </v-alert>
            <slot />
          </v-card-text>
          <v-card-actions v-if="$slots.footer">
            <slot name="footer" />
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  error?: string | null
}>()

defineEmits<{
  'clear-error': []
}>()
</script>
