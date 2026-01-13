import { createRouter, createWebHistory } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/stores/auth'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import EmployeeDashboard from '../views/EmployeeDashboard.vue'
import OwnerDashboard from '../views/OwnerDashboard.vue'

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
      path: '/onboarding/create-company',
      name: 'onboarding-create-company',
      component: () => import('../views/onboarding/CreateCompany.vue'),
      meta: { requiresAuth: true },
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
                     { title: 'Attendance', to: '/admin/settings/attendance' },
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
                 component: () => import('../components/employee/EmployeesList.vue'),
               },
               {
                 path: 'payroll',
                 name: 'admin-payroll',
                 component: () => import('../components/payroll/PayrollRunList.vue'),
               },
               {
                 path: 'attendance',
                 name: 'admin-attendance',
                 component: () => import('../components/attendance/AttendanceManage.vue'),
               },
               {
                 path: 'leave-requests',
                 name: 'admin-leave-requests',
                 component: () => import('../components/leave/LeaveRequestList.vue'),
               },
               {
                 path: 'loans',
                 name: 'admin-loans',
                 component: () => import('../components/loan/LoanList.vue'),
               },
               {
                 path: 'deductions',
                 name: 'admin-deductions',
                 component: () => import('../components/deduction/DeductionList.vue'),
               },
               {
                 path: 'contributions',
                 name: 'admin-contributions',
                 component: () => import('../components/contribution/AdminContributionManager.vue'),
               },
               {
                 path: 'salaries',
                 name: 'admin-salaries',
                 component: () => import('../components/salary/AdminSalaryManager.vue'),
               },
               {
                 path: 'attendance-review',
                 name: 'admin-attendance-review',
                 component: () => import('../components/attendance/AttendanceReviewQueue.vue'),
               },
               {
                 path: 'settings/attendance',
                 name: 'admin-settings-attendance',
                 component: () => import('../components/attendance/AttendanceSettings.vue'),
               },
               // Add more admin routes here
      ],
    },
    {
      path: '/owner',
      component: DashboardLayout,
      meta: { requiresAuth: true, role: 'owner' },
      props: {
        title: 'Owner Dashboard',
        appBarTitle: 'Payroll System - Owner',
        menuItems: [
          { title: 'Dashboard', to: '/owner', icon: 'mdi-view-dashboard' },
          { title: 'Manage Employees', to: '/owner/employees', icon: 'mdi-account-group' },
          { title: 'Payroll', to: '/owner/payroll', icon: 'mdi-cash-multiple' },
          { title: 'Attendance Management', to: '/owner/attendance', icon: 'mdi-calendar-clock' },
          { title: 'Leave Requests', to: '/owner/leave-requests', icon: 'mdi-calendar-remove' },
          { title: 'Loans', to: '/owner/loans', icon: 'mdi-cash-multiple' },
          { title: 'Deductions', to: '/owner/deductions', icon: 'mdi-cash-minus' },
          { title: 'Contributions', to: '/owner/contributions', icon: 'mdi-account-cash' },
          { title: 'Salaries', to: '/owner/salaries', icon: 'mdi-cash' },
          { title: 'Attendance Review', to: '/owner/attendance-review', icon: 'mdi-alert-circle-outline' },
          { title: 'Invitations', to: '/owner/invitations', icon: 'mdi-email-plus' },
          {
            title: 'Settings',
            icon: 'mdi-cog',
            children: [
              { title: 'Attendance', to: '/owner/settings/attendance' },
            ],
          },
        ],
      },
      children: [
        {
          path: '',
          name: 'owner-dashboard',
          component: OwnerDashboard,
        },
        {
          path: 'employees',
          name: 'owner-employees',
          component: () => import('../components/employee/EmployeesList.vue'),
        },
        {
          path: 'payroll',
          name: 'owner-payroll',
          component: () => import('../components/payroll/PayrollRunList.vue'),
        },
        {
          path: 'attendance',
          name: 'owner-attendance',
          component: () => import('../components/attendance/AttendanceManage.vue'),
        },
        {
          path: 'leave-requests',
          name: 'owner-leave-requests',
          component: () => import('../components/leave/LeaveRequestList.vue'),
        },
        {
          path: 'loans',
          name: 'owner-loans',
          component: () => import('../components/loan/LoanList.vue'),
        },
        {
          path: 'deductions',
          name: 'owner-deductions',
          component: () => import('../components/deduction/DeductionList.vue'),
        },
        {
          path: 'contributions',
          name: 'owner-contributions',
          component: () => import('../components/contribution/AdminContributionManager.vue'),
        },
        {
          path: 'salaries',
          name: 'owner-salaries',
          component: () => import('../components/salary/AdminSalaryManager.vue'),
        },
        {
          path: 'attendance-review',
          name: 'owner-attendance-review',
          component: () => import('../components/attendance/AttendanceReviewQueue.vue'),
        },
        {
          path: 'invitations',
          name: 'owner-invitations',
          component: () => import('../components/invitation/InvitationList.vue'),
        },
        {
          path: 'settings/attendance',
          name: 'owner-settings-attendance',
          component: () => import('../components/attendance/AttendanceSettings.vue'),
        },
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
          component: () => import('../components/attendance/EmployeeAttendance.vue'),
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
    return
  }

  if (to.meta.role && user && user.role !== to.meta.role) {
    if (user.role === 'owner') {
      next('/owner')
    } else if (user.role === 'admin') {
      next('/admin')
    } else if (user.role === 'employee') {
      next('/employee')
    } else {
      next('/login')
    }
    return
  }

  // For authenticated routes, ensure company is selected
  // BUT only for routes that require company context (not /user or /companies)
  if (to.meta.requiresAuth && isAuthenticated && to.path !== '/user' && !to.path.startsWith('/companies')) {
    const { useCompanyStore } = await import('@/stores/company')
    const companyStore = useCompanyStore()

    // Fetch companies if not loaded
    // Note: companies is returned as data ref from Vue Query, Pinia auto-unwraps it
    const companies = companyStore.companies
    if (!companies || companies.length === 0) {
      await companyStore.fetchCompanies()
    }

    // Re-access companies after potential fetch
    const updatedCompanies = companyStore.companies
    if (updatedCompanies && updatedCompanies.length > 0) {
      const activeUuid = companyStore.activeCompanyUuid
      const firstCompany = updatedCompanies[0]
      if (!activeUuid && firstCompany) {
        companyStore.setActiveCompany(firstCompany.uuid)
      }
    } else if (updatedCompanies && updatedCompanies.length === 0) {
      // No companies available - this shouldn't happen but handle gracefully
      console.warn('No companies available for user')
    }
  }

  next()
})

export default router
