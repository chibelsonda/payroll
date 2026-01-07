<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import NotificationSnackbar from './components/NotificationSnackbar.vue'

const auth = useAuthStore()

onMounted(() => {
  auth.fetchUser()
})
</script>

<template>
  <v-app>
    <RouterView />
    <NotificationSnackbar />
  </v-app>
</template>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
