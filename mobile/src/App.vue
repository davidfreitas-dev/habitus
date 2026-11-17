<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import { Capacitor } from '@capacitor/core';
import { ScreenOrientation } from '@capacitor/screen-orientation';
import { useNetwork } from '@/composables/useNetwork';
import { useStatusBar } from '@/composables/useStatusBar';
import { useNotifications } from '@/composables/useNotifications';
import { useDeepLinks } from '@/composables/useDeepLinks';
import { useAuthStore } from '@/stores/authStore';
import { useThemeStore } from '@/stores/themeStore';
import { useHabitStore } from '@/stores/habitsStore';
import { notificationService } from '@/services/notificationService';

const { isConnected } = useNetwork();
const { setStatusBar, Style } = useStatusBar();
const { checkPermission } = useNotifications();
useDeepLinks();

const authStore = useAuthStore();
const themeStore = useThemeStore();
const habitStore = useHabitStore();

const applyTheme = (isDark: boolean) => {
  document.body.classList.toggle('dark', isDark);
  setStatusBar(isDark ? Style.Dark : Style.Light);
};

watch(() => themeStore.isDarkMode, (isDark) => {
  applyTheme(isDark);
});

onMounted(async () => {
  if (themeStore.isInitialLoad) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    themeStore.setDarkMode(prefersDark);
  } else {
    applyTheme(themeStore.isDarkMode);
  }

  if (Capacitor.isNativePlatform()) {
    await ScreenOrientation.lock({ orientation: 'portrait' });

    const granted = await checkPermission();
    if (granted && authStore.isAuthenticated) {
      try {
        const habits = await habitStore.fetchAllHabits();
        await notificationService.rescheduleAllNotifications(habits);
      } catch (error) {
        console.error('Error rescheduling notifications:', error);
      }
    }
  }
});
</script>

<template>
  <ion-app>
    <div v-if="!isConnected" class="offline-banner">
      Você está offline
    </div>
    <ion-router-outlet />
  </ion-app>
</template>

<style scoped>
.offline-banner {
  background: var(--ion-color-danger);
  color: white;
  text-align: center;
  padding: calc(var(--safe-area-inset-top, env(safe-area-inset-top, 0px)) + 6px) 12px 6px;
  font-size: 0.85rem;
  font-weight: 600;
  z-index: 9999;
}
</style>
