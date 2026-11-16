import { Preferences } from '@capacitor/preferences';

const PREFIX = 'onboarding:';

export type OnboardingStepKey = 'welcome' | 'home' | 'day' | 'form' | 'stats' | 'options';

/**
 * Composable for managing onboarding step progress.
 * Persists which onboarding steps have been seen using Capacitor Preferences.
 */
export function useOnboarding() {
  async function isStepSeen(step: OnboardingStepKey | string): Promise<boolean> {
    const { value } = await Preferences.get({ key: PREFIX + step });
    return value === 'true';
  }

  async function markStepSeen(step: OnboardingStepKey | string): Promise<void> {
    await Preferences.set({ key: PREFIX + step, value: 'true' });
  }

  async function resetOnboarding(steps: Array<OnboardingStepKey | string>): Promise<void> {
    await Promise.all(steps.map(s => Preferences.remove({ key: PREFIX + s })));
  }

  return { isStepSeen, markStepSeen, resetOnboarding };
}
