# Observabilidade e Rastreamento de Erros com Sentry

O monitoramento com Sentry no aplicativo móvel permite capturar falhas nativas (iOS/Android crash) e exceções no runtime do JavaScript/Vue, complementando os logs de banco de dados (`error_logs`) que a API já possui.

---

## 1. Pacotes Recomendados

Para uma aplicação Ionic Vue com Capacitor, a integração recomendada utiliza:
- `@sentry/capacitor` (captura crashes das camadas nativas iOS e Android)
- `@sentry/vue` (captura exceções do ciclo de vida dos componentes Vue e Vue Router)

```bash
docker compose exec mobile npm install @sentry/capacitor @sentry/vue
```

---

## 2. Inicialização no `src/main.ts`

```ts
import { createApp } from 'vue';
import * as Sentry from '@sentry/capacitor';
import * as SentryVue from '@sentry/vue';
import App from './App.vue';
import router from './router';

const app = createApp(App);

if (import.meta.env.PROD) {
  Sentry.init(
    {
      app,
      dsn: import.meta.env.VITE_SENTRY_DSN,
      release: `habitus@${import.meta.env.VITE_APP_VERSION || '0.0.11'}`,
      dist: '1',
      integrations: [
        SentryVue.browserTracingIntegration({ router }),
      ],
      // Tracing de performance (ajuste para 0.2 em produção de alto volume)
      tracesSampleRate: 1.0,
      // Não capturar erros esperados de rede offline
      beforeSend(event, hint) {
        const error = hint.originalException;
        if (error && typeof error === 'object' && 'isAxiosError' in error) {
          // Erros de timeout ou falta de internet não são bugs do app
          if (!window.navigator.onLine) {
            return null;
          }
        }
        return event;
      },
    },
    // Inicializador do SDK Vue
    SentryVue.init,
  );
}

app.use(router);
app.mount('#app');
```

---

## 3. Identificação do Usuário Logado

Ao autenticar no `authStore.ts`, registre o ID anônimo no Sentry para facilitar a depuração de chamadas de suporte:

```ts
import * as Sentry from '@sentry/capacitor';

// No método de login bem-sucedido:
export function setSentryUser(userId: number): void {
  Sentry.setUser({ id: String(userId) });
}

// No logout:
export function clearSentryUser(): void {
  Sentry.setUser(null);
}
```

---

## 4. Captura Manual de Exceções em Services

Em blocos `catch` de fluxos críticos (ex: agendamento de notificações ou sincronização de dados):

```ts
import * as Sentry from '@sentry/capacitor';

try {
  await syncHabits();
} catch (error) {
  Sentry.captureException(error, {
    extra: {
      action: 'syncHabits',
      timestamp: new Date().toISOString(),
    },
  });
  throw error;
}
```
