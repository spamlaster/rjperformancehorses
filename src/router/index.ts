import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import type { RouteRecordRaw } from 'vue-router'

// Public pages
import HomePage from '@/pages/public/HomePage.vue'
import AboutPage from '@/pages/public/AboutPage.vue'
import LessonsPage from '@/pages/public/LessonsPage.vue'
import PricingPage from '@/pages/public/PricingPage.vue'
import FAQPage from '@/pages/public/FAQPage.vue'
import ContactPage from '@/pages/public/ContactPage.vue'
import BookingWizardPage from '@/pages/public/BookingWizardPage.vue'

// Admin pages
import LoginPage from '@/pages/admin/LoginPage.vue'
import DashboardPage from '@/pages/admin/DashboardPage.vue'
import RidersPage from '@/pages/admin/RidersPage.vue'
import HorsesPage from '@/pages/admin/HorsesPage.vue'
import BookingsPage from '@/pages/admin/BookingsPage.vue'
import AvailabilityPage from '@/pages/admin/AvailabilityPage.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: HomePage,
  },
  {
    path: '/about',
    name: 'About',
    component: AboutPage,
  },
  {
    path: '/lessons',
    name: 'Lessons',
    component: LessonsPage,
  },
  {
    path: '/pricing',
    name: 'Pricing',
    component: PricingPage,
  },
  {
    path: '/faq',
    name: 'FAQ',
    component: FAQPage,
  },
  {
    path: '/contact',
    name: 'Contact',
    component: ContactPage,
  },
  {
    path: '/book',
    name: 'Booking',
    component: BookingWizardPage,
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: LoginPage,
  },
  {
    path: '/admin/dashboard',
    name: 'Dashboard',
    component: DashboardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/riders',
    name: 'AdminRiders',
    component: RidersPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/horses',
    name: 'AdminHorses',
    component: HorsesPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/bookings',
    name: 'AdminBookings',
    component: BookingsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/availability',
    name: 'AdminAvailability',
    component: AvailabilityPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  },
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth) {
    // Check if user is authenticated AND is an admin
    if (authStore.isAuthenticated && authStore.isAdmin) {
      next()
    } else if (authStore.isLoading) {
      // Still checking auth state, wait a bit and try again
      setTimeout(() => {
        if (authStore.isAuthenticated && authStore.isAdmin) {
          next()
        } else {
          next({
            path: '/admin/login',
            query: { redirect: to.fullPath },
          })
        }
      }, 100)
    } else {
      // Not authenticated or not admin
      next({
        path: '/admin/login',
        query: { redirect: to.fullPath },
      })
    }
  } else {
    next()
  }
})

export default router
