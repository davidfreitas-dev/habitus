# Deep Links & App Links

Deep linking permite que links da web (como links de verificação de e-mail e redefinição de senha) abram diretamente o aplicativo móvel no dispositivo do usuário em vez do navegador.

---

## 1. Como Funciona no Habitus

```text
E-mail com link: https://api.seudominio.com/verify-email?token=xyz
                     ↓
             (Usuário clica)
                     ↓
[iOS: Universal Links] ou [Android: App Links]
                     ↓
Abre o Habitus (com @capacitor/app appUrlOpen)
                     ↓
Vue Router navega para /auth/verify-email?token=xyz
```

---

## 2. Configuração no Backend (Já existente)

O backend em Slim Framework 4 já possui o [WellKnownController.php](file:///Users/davidfreitas/www/habits/api/src/Presentation/Api/V1/Controller/WellKnownController.php) configurado:
- **iOS**: `GET /.well-known/apple-app-site-association`
  - Associa o `appID` (`TEAM_ID.br.dev.davidfreitas.habitus`) com a rota `/verify-email`.
- **Android**: `GET /.well-known/assetlinks.json`
  - Associa o `package_name` (`br.dev.davidfreitas.habitus`) e a chave SHA256 com a ação `handle_all_urls`.

---

## 3. Capturando a URL no Frontend com Capacitor 8 (`@capacitor/app`)

No `App.vue` ou em um composable dedicado `useDeepLinks.ts`:

```ts
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { App, type URLOpenListenerEvent } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';

export function useDeepLinks() {
  const router = useRouter();

  onMounted(() => {
    if (!Capacitor.isNativePlatform()) return;

    App.addListener('appUrlOpen', (event: URLOpenListenerEvent) => {
      // Exemplo de URL recebida:
      // https://seudominio.com/verify-email?token=abcdef123456
      // ou custom scheme: habitus://verify-email?token=abcdef123456

      try {
        const url = new URL(event.url);
        const path = url.pathname;
        const search = url.search;

        if (path.includes('/verify-email')) {
          router.push(`/auth/verify-email${search}`);
        } else if (path.includes('/reset-password')) {
          router.push(`/auth/reset-password${search}`);
        }
      } catch (err) {
        console.error('[DeepLink] Erro ao processar URL:', err);
      }
    });
  });
}
```

---

## 4. Configuração Nativa Necessária

### Android (`android/app/src/main/AndroidManifest.xml`):
```xml
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="seudominio.com" android:pathPrefix="/verify-email" />
</intent-filter>
```

### iOS (`ios/App/App/App.entitlements`):
Adicionar a capacidade **Associated Domains**:
```xml
<key>com.apple.developer.associated-domains</key>
<array>
    <string>applinks:seudominio.com</string>
</array>
```
