import { Preferences } from '@capacitor/preferences';

const CONSENT_STORAGE_KEY = 'privacy:analytics_consent';

export type AnalyticsEvent =
  | { name: 'habit_created'; params?: { weekDaysCount: number; hasReminder: boolean } }
  | { name: 'habit_updated'; params: { habitId: number | string; hasReminder: boolean } }
  | { name: 'habit_deleted'; params: { habitId: number | string } }
  | { name: 'habit_toggled'; params: { habitId: number | string; date: string } }
  | { name: 'habit_toggled_offline'; params: { habitId: number | string; date: string } }
  | { name: 'habit_toggled_offline_fallback'; params: { habitId: number | string; date: string } }
  | { name: 'offline_sync_completed'; params: { syncedCount: number; failedCount: number } }
  | { name: 'streak_achieved'; params: { count: number } }
  | { name: 'screen_view'; params: { screenName: string } }
  | { name: 'onboarding_reset' };

let cachedConsent: boolean | null = null;

export const analyticsService = {
  async getConsent(): Promise<boolean> {
    if (cachedConsent !== null) {
      return cachedConsent;
    }

    try {
      const { value } = await Preferences.get({ key: CONSENT_STORAGE_KEY });
      // Por padrão, telemetria anônima inicia ativa até que o usuário opte por desativar (opt-out LGPD)
      cachedConsent = value === null ? true : value === 'true';
      return cachedConsent;
    } catch {
      return false;
    }
  },

  async setConsent(enabled: boolean): Promise<void> {
    cachedConsent = enabled;
    try {
      await Preferences.set({
        key: CONSENT_STORAGE_KEY,
        value: String(enabled),
      });
    } catch (err) {
      console.warn('[Analytics] Falha ao salvar preferência de consentimento:', err);
    }
  },

  async logEvent(event: AnalyticsEvent): Promise<void> {
    try {
      const hasConsent = await this.getConsent();
      if (!hasConsent) return;

      if (import.meta.env.DEV) {
        console.info(`[Analytics: ${event.name}]`, 'params' in event ? event.params : {});
      }
    } catch {
      // Isolamento de erros: falhas analíticas nunca devem impactar a experiência do usuário
    }
  },
};
