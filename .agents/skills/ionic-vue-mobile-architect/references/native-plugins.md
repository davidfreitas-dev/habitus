# Plugins Nativos Essenciais (Capacitor 8)

O Habitus utiliza o conjunto oficial de plugins do Capacitor para entregar uma experiência nativa polida. Esta referência consolida padrões de uso com TypeScript e tratamento defensivo para Web e Dispositivos Móveis.

---

## 1. Checagem de Plataforma e Execução Defensiva

Muitos plugins nativos não têm implementação completa no navegador web. Sempre proteja chamadas de hardware com verificação de plataforma:

```ts
import { Capacitor } from '@capacitor/core';

export const isNative = Capacitor.isNativePlatform();
export const isIOS = Capacitor.getPlatform() === 'ios';
export const isAndroid = Capacitor.getPlatform() === 'android';
```

---

## 2. Haptics (Feedback Tátil / Vibração)

Utilizado ao alternar o status de um hábito (toggle) ou concluir metas diárias:

```ts
import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';
import { Capacitor } from '@capacitor/core';

export async function triggerHapticFeedback(type: 'light' | 'medium' | 'heavy' | 'success' | 'warning' | 'error'): Promise<void> {
  if (!Capacitor.isNativePlatform()) return;

  try {
    switch (type) {
      case 'light':
        await Haptics.impact({ style: ImpactStyle.Light });
        break;
      case 'medium':
        await Haptics.impact({ style: ImpactStyle.Medium });
        break;
      case 'heavy':
        await Haptics.impact({ style: ImpactStyle.Heavy });
        break;
      case 'success':
        await Haptics.notification({ type: NotificationType.Success });
        break;
      case 'warning':
        await Haptics.notification({ type: NotificationType.Warning });
        break;
      case 'error':
        await Haptics.notification({ type: NotificationType.Error });
        break;
    }
  } catch (err) {
    console.warn('[Haptics] Falha ao disparar feedback:', err);
  }
}
```

---

## 3. Network (Detecção de Conexão Offline)

Gerenciado pelo composable `src/composables/useNetwork.ts`:

```ts
import { ref, onMounted, onUnmounted } from 'vue';
import { Network, type ConnectionStatus } from '@capacitor/network';
import type { PluginListenerHandle } from '@capacitor/core';

export function useNetwork() {
  const isOnline = ref(true);
  const connectionType = ref<string>('unknown');
  let networkListener: PluginListenerHandle | null = null;

  onMounted(async () => {
    const status = await Network.getStatus();
    isOnline.value = status.connected;
    connectionType.value = status.connectionType;

    networkListener = await Network.addListener('networkStatusChange', (status: ConnectionStatus) => {
      isOnline.value = status.connected;
      connectionType.value = status.connectionType;
    });
  });

  onUnmounted(() => {
    networkListener?.remove();
  });

  return { isOnline, connectionType };
}
```

---

## 4. SystemBars (Status Bar / Navigation Bar Edge-to-Edge) & Keyboard

### SystemBars (`@capacitor/core`):
- No Capacitor 8 (Android 15/16 Edge-to-Edge e iOS), utilize a API nativa `SystemBars` embutida no `@capacitor/core` (em vez do antigo `@capacitor/status-bar`, cujas APIs de cor de barra foram descontinuadas no Android 15):
```ts
import { Capacitor, SystemBars, SystemBarsStyle as Style } from '@capacitor/core';

export async function setStatusBarStyle(isDark: boolean): Promise<void> {
  if (!Capacitor.isNativePlatform()) return;

  await SystemBars.setStyle({
    style: isDark ? Style.Dark : Style.Light,
  });
}
```

### Keyboard:
- Utilize o plugin `@capacitor/keyboard` para evitar que o teclado nativo sobreponha campos de input em modais e formulários:
```ts
import { Keyboard } from '@capacitor/keyboard';

// Configuração no capacitor.config.json:
// "Keyboard": { "resize": "body", "style": "DARK" }
```

---

## 5. Screen Orientation

Para telas de estatísticas e gráficos de resumo que exigem orientação retrato:

```ts
import { ScreenOrientation } from '@capacitor/screen-orientation';
import { Capacitor } from '@capacitor/core';

export async function lockToPortrait(): Promise<void> {
  if (!Capacitor.isNativePlatform()) return;
  await ScreenOrientation.lock({ orientation: 'portrait' });
}
```
