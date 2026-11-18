import { ref, onMounted, onUnmounted } from 'vue';
import { Network, type ConnectionStatus } from '@capacitor/network';
import type { PluginListenerHandle } from '@capacitor/core';
import { useToast } from '@/composables/useToast';

const isConnected = ref(true);
let listenerHandle: PluginListenerHandle | null = null;
let activeSubscribers = 0;

export function useNetwork() {
  const { showToast } = useToast();

  const handleNetworkChange = ({ connected }: ConnectionStatus) => {
    const wasDisconnected = isConnected.value === false;
    isConnected.value = connected;

    if (!connected) {
      showToast('danger', 'Sem conexão com a internet.');
    } else if (wasDisconnected) {
      showToast('success', 'Conexão restaurada.');
    }
  };

  onMounted(async () => {
    activeSubscribers++;

    try {
      const status = await Network.getStatus();
      isConnected.value = status.connected;
    } catch {
      isConnected.value = typeof navigator !== 'undefined' ? navigator.onLine : true;
    }

    if (!listenerHandle) {
      try {
        listenerHandle = await Network.addListener('networkStatusChange', handleNetworkChange);
      } catch {
        // Fallback for environments where Network plugin listener is unsupported
      }
    }
  });

  onUnmounted(async () => {
    activeSubscribers--;
    if (activeSubscribers <= 0 && listenerHandle) {
      await listenerHandle.remove();
      listenerHandle = null;
      activeSubscribers = 0;
    }
  });

  return { isConnected };
}
