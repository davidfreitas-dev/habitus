import { Preferences } from '@capacitor/preferences';

const PREFIX = 'onboarding:';

export type OnboardingStepKey = 'welcome' | 'home' | 'day' | 'form' | 'stats' | 'options' | 'habits';

const stepCache = new Map<string, boolean>();

/**
 * Composable for managing onboarding step progress.
 * Persists which onboarding steps have been seen using Capacitor Preferences
 * with an in-memory cache to avoid redundant native bridge calls on navigation.
 */
export function useOnboarding() {
  async function isStepSeen(step: OnboardingStepKey | string): Promise<boolean> {
    if (stepCache.has(step)) {
      return stepCache.get(step)!;
    }

    const { value } = await Preferences.get({ key: PREFIX + step });
    const seen = value === 'true';
    stepCache.set(step, seen);
    return seen;
  }

  async function markStepSeen(step: OnboardingStepKey | string): Promise<void> {
    stepCache.set(step, true);
    await Preferences.set({ key: PREFIX + step, value: 'true' });
  }

  async function resetOnboarding(steps: Array<OnboardingStepKey | string>): Promise<void> {
    for (const s of steps) {
      stepCache.delete(s);
    }
    await Promise.all(steps.map(s => Preferences.remove({ key: PREFIX + s })));
  }

  function clearOnboardingCache(): void {
    stepCache.clear();
  }

  return { isStepSeen, markStepSeen, resetOnboarding, clearOnboardingCache };
}
