import { describe, expect, it, vi, beforeEach } from 'vitest';
import { Preferences } from '@capacitor/preferences';
import { useOnboarding } from '@/composables/useOnboarding';

vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: vi.fn(),
    set: vi.fn(),
    remove: vi.fn(),
  },
}));

describe('useOnboarding', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('checks if an onboarding step has been seen', async () => {
    const { isStepSeen } = useOnboarding();

    vi.mocked(Preferences.get).mockResolvedValueOnce({ value: 'true' });
    expect(await isStepSeen('welcome')).toBe(true);
    expect(Preferences.get).toHaveBeenCalledWith({ key: 'onboarding:welcome' });

    vi.mocked(Preferences.get).mockResolvedValueOnce({ value: null });
    expect(await isStepSeen('home')).toBe(false);
  });

  it('marks an onboarding step as seen', async () => {
    const { markStepSeen } = useOnboarding();

    await markStepSeen('day');

    expect(Preferences.set).toHaveBeenCalledWith({
      key: 'onboarding:day',
      value: 'true',
    });
  });

  it('resets selected onboarding steps', async () => {
    const { resetOnboarding } = useOnboarding();

    await resetOnboarding(['welcome', 'home', 'stats']);

    expect(Preferences.remove).toHaveBeenCalledTimes(3);
    expect(Preferences.remove).toHaveBeenCalledWith({ key: 'onboarding:welcome' });
    expect(Preferences.remove).toHaveBeenCalledWith({ key: 'onboarding:home' });
    expect(Preferences.remove).toHaveBeenCalledWith({ key: 'onboarding:stats' });
  });
});
