import { createRouter, createWebHistory } from '@ionic/vue-router';
import type { RouteRecordRaw, RouteLocationNormalized } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { alertController } from '@ionic/vue';
import { useOnboarding } from '@/composables/useOnboarding';

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
  }
}

const routes: Array<RouteRecordRaw> = [
  {
    path: '/signup',
    name: 'Signup',
    component: () => import('@/views/auth/Signup.vue'),
  },
  {
    path: '/signin',
    name: 'Signin',
    component: () => import('@/views/auth/Signin.vue'),
  },
  {
    path: '/forgot',
    name: 'Forgot',
    component: () => import('@/views/auth/Forgot.vue'),
  },
  {
    path: '/forgot/token',
    name: 'Token',
    component: () => import('@/views/auth/Token.vue'),
  },
  {
    path: '/forgot/reset',
    name: 'Reset',
    component: () => import('@/views/auth/Reset.vue'),
    props: (route: RouteLocationNormalized) => ({ data: route.query.data })
  },
  {
    path: '/verify-email',
    name: 'VerifyEmail',
    component: () => import('@/views/auth/VerifyEmail.vue'),
    props: (route: RouteLocationNormalized) => ({ token: route.query.token })
  },
  {
    path: '/',
    redirect: '/tabs/home'
  },
  {
    path: '/tabs',
    component: () => import('@/views/TabsPage.vue'),
    children: [
      {
        path: '',
        redirect: '/tabs/home'
      },
      {
        path: 'home',
        name: 'Home',
        component: () => import('@/views/habits/Home.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'habits',
        name: 'HabitsList',
        component: () => import('@/views/habits/HabitsList.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'statistics',
        name: 'Statistics',
        component: () => import('@/views/habits/Statistics.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'options',
        name: 'Options',
        component: () => import('@/views/settings/Options.vue'),
        meta: { requiresAuth: true }
      },
    ]
  },
  {
    path: '/habit/:id?',
    name: 'Habit',
    component: () => import('@/views/habits/Habit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/day/:date',
    name: 'Day',
    redirect: (to) => ({ path: '/tabs/home', query: { date: to.params.date } }),
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/settings/Profile.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/password-change',
    name: 'PasswordChange',
    component: () => import('@/views/settings/PasswordChange.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/delete-account',
    name: 'DeleteAccount',
    component: () => import('@/views/settings/DeleteAccount.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/about',
    name: 'About',
    component: () => import('@/views/settings/About.vue')
  },
  {
    path: '/onboarding/welcome',
    name: 'OnboardingWelcome',
    component: () => import('@/views/onboarding/OnboardingWelcome.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

let isAlertShowing = false;

const showSessionExpiredAlertAndRedirect = async () => {
  if (isAlertShowing) return;
  isAlertShowing = true;

  const alert = await alertController.create({
    header: 'Sessão Expirada',
    message: 'Sua sessão expirou. Por favor, faça login novamente.',
    cssClass: 'alert-box',
    buttons: [
      {
        text: 'OK',
        handler: async () => {
          isAlertShowing = false;
          router.replace('/signin');
        },
      },
    ],
  });

  await alert.present();

  const { role } = await alert.onDidDismiss();
  if (role === 'backdrop' || role === 'cancel') {
    isAlertShowing = false;
    if (router.currentRoute.value.path !== '/signin') {
      router.replace('/signin');
    }
  }
};

router.beforeEach(async (to) => {
  if (typeof document !== 'undefined' && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }

  const authStore = useAuthStore();

  if (!to.meta.requiresAuth) {
    return true;
  }

  let isAuthenticated = authStore.isAuthenticated;

  if (!isAuthenticated) {
    try {
      const refreshed = await authStore.refreshAccessToken();
      if (refreshed) {
        isAuthenticated = true;
      } else {
        authStore.sessionExpired = false;
      }
    } catch {
      // Erro ao renovar token, prossegue para o redirecionamento
      authStore.sessionExpired = false;
    }
  }

  if (isAuthenticated) {
    const { isStepSeen } = useOnboarding();
    const seen = await isStepSeen('welcome');

    if (to.name === 'OnboardingWelcome') {
      if (seen) {
        return '/tabs/home';
      }
      return true;
    }

    if (!seen) {
      return { name: 'OnboardingWelcome' };
    }

    return true;
  }

  if (authStore.sessionExpired) {
    authStore.sessionExpired = false;
    showSessionExpiredAlertAndRedirect();
    return false;
  }
  
  return { name: 'Signin', replace: true };
});

router.afterEach(() => {
  if (
    typeof document !== 'undefined' &&
    document.activeElement instanceof HTMLElement &&
    document.activeElement !== document.body
  ) {
    if (document.activeElement.closest('[aria-hidden="true"]')) {
      document.activeElement.blur();
    }
  }
});

export default router;
