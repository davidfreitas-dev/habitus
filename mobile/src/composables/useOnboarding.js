import { Preferences } from '@capacitor/preferences';

const PREFIX = 'onboarding:';

/**
 * Composable for managing onboarding step progress.
 * Persists which onboarding steps have been seen using Capacitor Preferences.
 *
 * @returns {{ isStepSeen: (step: string) => Promise<boolean>, markStepSeen: (step: string) => Promise<void>, resetOnboarding: (steps: string[]) => Promise<void> }}
 */
export function useOnboarding() {
  /**
   * Checks if a given onboarding step has already been seen.
   * @param {string} step - The step identifier (e.g., 'welcome', 'home', 'form')
   * @returns {Promise<boolean>}
   */
  async function isStepSeen(step) {
    const { value } = await Preferences.get({ key: PREFIX + step });
    return value === 'true';
  }

  /**
   * Marks a given onboarding step as seen.
   * @param {string} step - The step identifier
   * @returns {Promise<void>}
   */
  async function markStepSeen(step) {
    await Preferences.set({ key: PREFIX + step, value: 'true' });
  }

  /**
   * Resets onboarding progress for the given steps.
   * @param {string[]} steps - Array of step identifiers to reset
   * @returns {Promise<void>}
   */
  async function resetOnboarding(steps) {
    await Promise.all(steps.map(s => Preferences.remove({ key: PREFIX + s })));
  }

  return { isStepSeen, markStepSeen, resetOnboarding };
}
