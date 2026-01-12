<template>
  <v-container fluid class="pa-6">
    <v-row>
      <v-col cols="12">
        <div class="text-h4 font-weight-bold mb-2">Owner Dashboard</div>
        <div class="text-body-1 text-medium-emphasis mb-6">
          Welcome! Manage your company and oversee all operations.
        </div>
      </v-col>
    </v-row>

    <v-row>
      <!-- Quick Stats Cards -->
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-body-2 text-medium-emphasis mb-1">Total Employees</div>
                <div class="text-h4 font-weight-bold">-</div>
              </div>
              <v-icon size="48" color="primary">mdi-account-group</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-body-2 text-medium-emphasis mb-1">Payroll Runs</div>
                <div class="text-h4 font-weight-bold">-</div>
              </div>
              <v-icon size="48" color="success">mdi-cash-multiple</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-body-2 text-medium-emphasis mb-1">Leave Requests</div>
                <div class="text-h4 font-weight-bold">-</div>
              </div>
              <v-icon size="48" color="warning">mdi-calendar-remove</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-body-2 text-medium-emphasis mb-1">Attendance</div>
                <div class="text-h4 font-weight-bold">-</div>
              </div>
              <v-icon size="48" color="info">mdi-calendar-clock</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Quick Actions -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">Quick Actions</v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" sm="6" md="3">
                <v-btn
                  to="/owner/employees"
                  color="primary"
                  variant="elevated"
                  block
                  size="large"
                  prepend-icon="mdi-account-group"
                >
                  Manage Employees
                </v-btn>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-btn
                  to="/owner/payroll"
                  color="success"
                  variant="elevated"
                  block
                  size="large"
                  prepend-icon="mdi-cash-multiple"
                >
                  Run Payroll
                </v-btn>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-btn
                  to="/owner/attendance"
                  color="info"
                  variant="elevated"
                  block
                  size="large"
                  prepend-icon="mdi-calendar-clock"
                >
                  Attendance
                </v-btn>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-btn
                  to="/owner/settings"
                  color="secondary"
                  variant="elevated"
                  block
                  size="large"
                  prepend-icon="mdi-cog"
                >
                  Settings
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
// Owner dashboard component
// Similar to admin dashboard but specifically for company owners
</script>
