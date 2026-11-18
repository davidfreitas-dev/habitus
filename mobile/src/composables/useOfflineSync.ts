import { getCurrentInstance, onMounted, onUnmounted, watch } from 'vue';
import { App } from '@capacitor/app';
import type { PluginListenerHandle } from '@capacitor/core';
import { useNetwork } from '@/composables/useNetwork';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/authStore';
import { useHabitStore } from '@/stores/habitsStore';

export function useOfflineSync() {
  const { isConnected } = useNetwork();
  const { showToast } = useToast();
  const authStore = useAuthStore();
  const habitStore = useHabitStore();

  let appStateListener: PluginListenerHandle | null = null;

  const syncNow = async () => {
    if (!isConnected.value || !authStore.isAuthenticated || habitStore.isSyncing) {
      return;
    }

    try {
      const { synced } = await habitStore.syncPendingHabits();
      if (synced > 0) {
        const msg =
          synced === 1
            ? '1 hábito sincronizado com sucesso.'
            : `${synced} hábitos sincronizados com sucesso.`;
        void showToast('success', msg);
      }
    } catch (error) {
      console.error('Offline sync failed:', error);
    }
  };

  // Trigger sync on reconnection
  watch(isConnected, (connected) => {
    if (connected && authStore.isAuthenticated) {
      void syncNow();
    }
  });

  // Trigger sync when user becomes authenticated
  watch(
    () => authStore.isAuthenticated,
    (authenticated) => {
      if (authenticated && isConnected.value) {
        void syncNow();
      }
    }
  );

  if (getCurrentInstance()) {
    onMounted(async () => {
      await habitStore.initPendingSyncCount();

      if (isConnected.value && authStore.isAuthenticated) {
        void syncNow();
      }

      try {
        appStateListener = await App.addListener('appStateChange', (state) => {
          if (state.isActive && isConnected.value && authStore.isAuthenticated) {
            void syncNow();
          }
        });
      } catch {
        // In web or environments where App plugin is unavailable
      }
    });

    onUnmounted(async () => {
      await appStateListener?.remove();
    });
  }

  return {
    syncNow,
  };
}
