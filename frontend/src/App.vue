<script setup lang="ts">
import GlobalLoading from './components/common/GlobalLoading.vue';
import NotificationSnackbar from './components/common/NotificationSnackbar.vue'
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
