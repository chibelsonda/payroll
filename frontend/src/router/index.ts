import { createRouter, createWebHistory } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/stores/auth'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import EmployeeDashboard from '../views/EmployeeDashboard.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
      meta: { guest: true },
    },
    {
      path: '/admin',
      component: DashboardLayout,
      meta: { requiresAuth: true, role: 'admin' },
      props: {
        title: 'Admin Dashboard',
        appBarTitle: 'Payroll System - Admin',
               menuItems: [
                 { title: 'Dashboard', to: '/admin', icon: 'mdi-view-dashboard' },
                 { title: 'Manage Employees', to: '/admin/employees', icon: 'mdi-account-group' },
                 { title: 'Payroll', to: '/admin/payroll', icon: 'mdi-cash-multiple' },
                 { title: 'Attendance Management', to: '/admin/attendance', icon: 'mdi-calendar-clock' },
                 { title: 'Leave Requests', to: '/admin/leave-requests', icon: 'mdi-calendar-remove' },
                 { title: 'Loans', to: '/admin/loans', icon: 'mdi-cash-multiple' },
                 { title: 'Deductions', to: '/admin/deductions', icon: 'mdi-cash-minus' },
                 { title: 'Contributions', to: '/admin/contributions', icon: 'mdi-account-cash' },
                 { title: 'Salaries', to: '/admin/salaries', icon: 'mdi-cash' },
                 { title: 'Attendance Review', to: '/admin/attendance-review', icon: 'mdi-alert-circle-outline' },
                 {
                   title: 'Settings',
                   icon: 'mdi-cog',
                   children: [
                     { title: 'General', to: '/admin/settings/general' },
                     { title: 'Notifications', to: '/admin/settings/notifications' },
                     { title: 'Security', to: '/admin/settings/security' },
                   ],
                 },
               ],
      },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: AdminDashboard,
        },
               {
                 path: 'employees',
                 name: 'admin-employees',
                 component: () => import('../components/EmployeesList.vue'),
               },
               {
                 path: 'payroll',
                 name: 'admin-payroll',
                 component: () => import('../components/PayrollRunList.vue'),
               },
               {
                 path: 'attendance',
                 name: 'admin-attendance',
                 component: () => import('../components/AttendanceManage.vue'),
               },
               {
                 path: 'leave-requests',
                 name: 'admin-leave-requests',
                 component: () => import('../components/LeaveRequestList.vue'),
               },
               {
                 path: 'loans',
                 name: 'admin-loans',
                 component: () => import('../components/LoanList.vue'),
               },
               {
                 path: 'deductions',
                 name: 'admin-deductions',
                 component: () => import('../components/DeductionList.vue'),
               },
               {
                 path: 'contributions',
                 name: 'admin-contributions',
                 component: () => import('../components/AdminContributionManager.vue'),
               },
               {
                 path: 'salaries',
                 name: 'admin-salaries',
                 component: () => import('../components/AdminSalaryManager.vue'),
               },
               {
                 path: 'attendance-review',
                 name: 'admin-attendance-review',
                 component: () => import('../components/AttendanceReviewQueue.vue'),
               },
               // Add more admin routes here
      ],
    },
    {
      path: '/employee',
      component: DashboardLayout,
      meta: { requiresAuth: true, role: 'employee' },
      props: {
        title: 'Employee Dashboard',
        appBarTitle: 'Payroll System - Employee',
        menuItems: [
          { title: 'Dashboard', to: '/employee', icon: 'mdi-view-dashboard' },
          { title: 'My Profile', to: '/employee/profile', icon: 'mdi-account' },
          { title: 'Attendance', to: '/employee/attendance', icon: 'mdi-calendar-clock' },
        ],
      },
      children: [
        {
          path: '',
          name: 'employee-dashboard',
          component: EmployeeDashboard,
        },
        {
          path: 'profile',
          name: 'employee-profile',
          component: EmployeeDashboard, // Using same component for now, can be replaced with dedicated profile component
        },
        {
          path: 'attendance',
          name: 'employee-attendance',
          component: () => import('../components/EmployeeAttendance.vue'),
        },
        // Add more employee routes here
      ],
    },
    {
      path: '/',
      redirect: '/login',
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()
  const queryClient = useQueryClient()

  // Only fetch user if we don't have user data yet and we're going to a protected route
  // Skip fetching if we just came from login/register (user data is already in cache)
  const isComingFromAuth = from.name === 'login' || from.name === 'register'

  // If coming from login/register, check cache directly since query might not be enabled yet
  if (isComingFromAuth && to.meta.requiresAuth) {
    const cachedUser = queryClient.getQueryData(['user'])
    if (cachedUser && !auth.user) {
      // Enable the query so auth.user becomes reactive
      auth.fetchUser().catch(() => {
        // Silently ignore errors
      })
    }
  } else if (to.meta.requiresAuth && !auth.user && !isComingFromAuth) {
    try {
      await auth.fetchUser()
    } catch {
      // Silently fail - user is not authenticated
    }
  }

  // Check authentication - use cached user if available when coming from auth pages
  const cachedUser = isComingFromAuth ? queryClient.getQueryData<{ role?: string }>(['user']) : null
  const user = auth.user || cachedUser
  const isAuthenticated = !!user

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
  } else if (to.meta.role && user && user.role !== to.meta.role) {
    if (user.role === 'admin') {
      next('/admin')
    } else if (user.role === 'employee') {
      next('/employee')
    } else {
      next('/login')
    }
  } else {
    next()
  }
})

export default router
