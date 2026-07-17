import { createRouter, createWebHistory } from '@ionic/vue-router';
import { useAuthStore } from '@/stores/auth';
import { alertController } from '@ionic/vue';

const routes = [
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
    props: route => ({ data: route.query.data })
  },
  {
    path: '/verify-email',
    name: 'VerifyEmail',
    component: () => import('@/views/auth/VerifyEmail.vue'),
    props: route => ({ token: route.query.token })
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
    component: () => import('@/views/habits/Day.vue'),
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
          router.push('/signin');
        },
      },
    ],
  });

  await alert.present();

  const { role } = await alert.onDidDismiss();
  if (role === 'backdrop' || role === 'cancel') {
    isAlertShowing = false;
    if (router.currentRoute.value.path !== '/signin') {
      router.push('/signin');
    }
  }
};

router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  if (!to.meta.requiresAuth) {
    return true;
  }

  if (authStore.isAuthenticated) {
    return true;
  }

  try {
    const refreshed = await authStore.refreshAccessToken();
    if (refreshed) {
      return true;
    }
  } catch (error) {
    // Erro ao renovar token, prossegue para o redirecionamento
  }

  if (authStore.sessionExpired) {
    authStore.sessionExpired = false;
    showSessionExpiredAlertAndRedirect();
    return false;
  }
  
  return { name: 'Signin' };
});

export default router;