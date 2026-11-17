import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { App as CapApp, type URLOpenListenerEvent } from '@capacitor/app';
import { Capacitor, type PluginListenerHandle } from '@capacitor/core';

/**
 * Resolve uma URL de Universal Link (https://...) ou Custom Scheme (habits://...)
 * para uma rota interna do Vue Router.
 */
export function resolveDeepLinkRoute(rawUrl: string): string | null {
  try {
    const url = new URL(rawUrl);
    const normalizedTarget = `${url.host}${url.pathname}`;
    const search = url.search || '';

    if (url.pathname === '/verify-email' || normalizedTarget.includes('verify-email')) {
      return `/verify-email${search}`;
    }

    if (url.pathname === '/forgot/reset' || normalizedTarget.includes('reset-password')) {
      return `/forgot/reset${search}`;
    }

    return null;
  } catch (err) {
    console.error('[DeepLink] Falha ao processar URL:', err);
    return null;
  }
}

export function useDeepLinks() {
  const router = useRouter();
  let appUrlListener: PluginListenerHandle | null = null;

  onMounted(async () => {
    if (!Capacitor.isNativePlatform()) return;

    appUrlListener = await CapApp.addListener('appUrlOpen', (event: URLOpenListenerEvent) => {
      const targetRoute = resolveDeepLinkRoute(event.url);
      if (targetRoute) {
        router.push(targetRoute);
      }
    });
  });

  onUnmounted(async () => {
    await appUrlListener?.remove();
  });
}
