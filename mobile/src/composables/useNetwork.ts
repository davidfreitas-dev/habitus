import { ref, onMounted, onUnmounted } from 'vue';
import { Network, type ConnectionStatus } from '@capacitor/network';
import type { PluginListenerHandle } from '@capacitor/core';
import { useToast } from '@/composables/useToast';

export function useNetwork() {
  const isConnected = ref(true);
  const { showToast } = useToast();
  let listenerHandle: PluginListenerHandle | null = null;

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
    const status = await Network.getStatus();
    isConnected.value = status.connected;
    listenerHandle = await Network.addListener('networkStatusChange', handleNetworkChange);
  });

  onUnmounted(async () => {
    await listenerHandle?.remove();
  });

  return { isConnected };
}
