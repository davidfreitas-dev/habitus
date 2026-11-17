# Consentimento (CMP / GDPR / LGPD) & Telemetria Analítica

Esta referência consolida as diretrizes para conformidade regulatória de privacidade do usuário e implementação desacoplada de eventos de telemetria e analytics no aplicativo móvel.

---

## 1. Consentimento e Privacidade (CMP / Google UMP / LGPD)

Se o aplicativo vier a utilizar serviços que coletem identificadores do dispositivo para métricas de produto ou anúncios personalizados (ex: Google AdMob ou SDKs analíticos de terceiros):

### Requisitos Regulatórios:
- **União Europeia (GDPR) e Reino Unido**: Exigem a apresentação prévia de um formulário de consentimento (*Consent Management Platform - CMP*) certificado pelo Google (via Google User Messaging Platform - UMP SDK).
- **Brasil (LGPD)**: Exige transparência e opção explícita de recusa ou revogação de consentimento de telemetria nas configurações (`src/views/settings/`).

### Padrão de Implementação:
1. Verifique se o consentimento é obrigatório na região do usuário antes de inicializar SDKs dependentes.
2. Armazene o status de consentimento utilizando `@capacitor/preferences`.
3. Ofereça um controle visível em *Configurações* para o usuário consultar ou revogar permissões de coleta a qualquer momento.

---

## 2. Padrão de Telemetria e Eventos Analíticos

Para medir o engajamento com os hábitos (ex: hábito criado, hábito alternado, streaks atingidas), mantenha uma camada desacoplada e tipada em `src/services/AnalyticsService.ts`:

```ts
export type AnalyticsEvent =
  | { name: 'habit_created'; params?: { category?: string } }
  | { name: 'habit_toggled'; params: { habitId: number; completed: boolean } }
  | { name: 'streak_achieved'; params: { count: number } }
  | { name: 'screen_view'; params: { screenName: string } };

export class AnalyticsService {
  public static logEvent(event: AnalyticsEvent): void {
    if (import.meta.env.DEV) {
      console.log(`[Analytics: ${event.name}]`, event.params);
      return;
    }

    // Ponto único de integração com SDKs (Firebase Analytics, PostHog, Mixpanel)
    // Exemplo:
    // FirebaseAnalytics.logEvent({ name: event.name, params: event.params });
  }
}
```

### Regras de Ouro para Telemetria:
- **Sem Dados Sensíveis (PII)**: Nunca envie senhas, e-mails, nomes pessoais ou tokens de autenticação como parâmetros de eventos analíticos.
- **Isolamento de Erros**: Falhas no envio de telemetria nunca devem quebrar a experiência do usuário ou travar a interface.
