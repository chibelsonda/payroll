<template>
  <v-container class="fill-height d-flex align-center justify-center" fluid>
    <v-card max-width="520" class="pa-6" elevation="3">
      <v-card-title class="text-h6 font-weight-bold mb-2">
        Verify your email
      </v-card-title>
      <v-card-text class="text-body-1">
        We’ve sent a verification link to your email. Please click the link to verify your account.
        After verification, you’ll proceed to create your company.
      </v-card-text>
      <v-card-actions class="justify-end">
        <v-btn color="primary" variant="flat" @click="goToVerify">
          Go to verification page
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goToVerify = () => {
  router.push('/verify-email')
}
</script>
