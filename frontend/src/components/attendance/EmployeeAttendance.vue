<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <!-- Time Clock Card -->
      <v-col cols="12" md="4">
        <AttendanceLogger />
      </v-col>

      <!-- Attendance Timeline -->
      <v-col cols="12" md="8">
        <AttendanceTimeline />
      </v-col>

      <!-- Attendance Summary -->
      <v-col cols="12">
        <AttendanceSummary />
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import AttendanceLogger from './AttendanceLogger.vue'
import AttendanceTimeline from './AttendanceTimeline.vue'
import AttendanceSummary from './AttendanceSummary.vue'
</script>
