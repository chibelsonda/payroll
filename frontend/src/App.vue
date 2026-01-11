<script setup lang="ts">
import GlobalLoading from './components/GlobalLoading.vue';
import NotificationSnackbar from './components/NotificationSnackbar.vue'
</script>

<template>
  <v-app>
    <RouterView />
    <NotificationSnackbar />
    <GlobalLoading />
  </v-app>
</template>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
