import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import StudentDashboard from '../views/StudentDashboard.vue'

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
        appBarTitle: 'Enrollment System - Admin',
        menuItems: [
          { title: 'Dashboard', to: '/admin', icon: 'mdi-view-dashboard' },
          { title: 'Manage Students', to: '/admin/students', icon: 'mdi-account-group' },
          { title: 'Manage Subjects', to: '/admin/subjects', icon: 'mdi-book-open-variant' },
          { title: 'Manage Enrollments', to: '/admin/enrollments', icon: 'mdi-clipboard-list' },
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
      path: '/student',
      component: DashboardLayout,
      meta: { requiresAuth: true, role: 'student' },
      props: {
        title: 'Student Dashboard',
        appBarTitle: 'Enrollment System - Student',
        menuItems: [
          { title: 'Dashboard', to: '/student', icon: 'mdi-view-dashboard' },
          { title: 'My Profile', to: '/student/profile', icon: 'mdi-account' },
          { title: 'Browse Subjects', to: '/student/subjects', icon: 'mdi-book-open-variant' },
          { title: 'My Enrollments', to: '/student/enrollments', icon: 'mdi-clipboard-list' },
        ],
      },
      children: [
        {
          path: '',
          name: 'student-dashboard',
          component: StudentDashboard,
        },
        // Add more student routes here
      ],
    },
    {
      path: '/',
      redirect: '/login',
    },
  ],
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.meta.role && auth.user?.role !== to.meta.role) {
    if (auth.isAdmin) {
      next('/admin')
    } else if (auth.isStudent) {
      next('/student')
    } else {
      next('/login')
    }
  } else {
    next()
  }
})

export default router
