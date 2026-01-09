import { createRouter, createWebHistory } from 'vue-router'
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
        ],
      },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: AdminDashboard,
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
        ],
      },
      children: [
        {
          path: '',
          name: 'employee-dashboard',
          component: EmployeeDashboard,
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

  // Only fetch user if we don't have user data yet and we're going to a protected route
  if (to.meta.requiresAuth && !auth.user) {
    try {
      await auth.fetchUser()
    } catch {
      // Silently fail - user is not authenticated
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.meta.role && auth.user?.role !== to.meta.role) {
    if (auth.isAdmin) {
      next('/admin')
    } else if (auth.isEmployee) {
      next('/employee')
    } else {
      next('/login')
    }
  } else {
    next()
  }
})

export default router
