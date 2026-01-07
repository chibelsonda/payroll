<script setup lang="ts">
import NotificationSnackbar from './components/NotificationSnackbar.vue'
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
