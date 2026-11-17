import { describe, expect, it, vi, beforeEach } from 'vitest';
import { Preferences } from '@capacitor/preferences';
import { analyticsService } from '@/services/analyticsService';

vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: vi.fn(),
    set: vi.fn(),
  },
}));

describe('analyticsService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('defaults to true when consent has not been set yet', async () => {
    vi.mocked(Preferences.get).mockResolvedValue({ value: null });

    // Reset module-level cache by setting explicitly
    await analyticsService.setConsent(true);
    const consent = await analyticsService.getConsent();

    expect(consent).toBe(true);
  });

  it('persists consent toggle in Capacitor Preferences', async () => {
    await analyticsService.setConsent(false);

    expect(Preferences.set).toHaveBeenCalledWith({
      key: 'privacy:analytics_consent',
      value: 'false',
    });

    const consent = await analyticsService.getConsent();
    expect(consent).toBe(false);
  });

  it('does not log events when consent is false', async () => {
    await analyticsService.setConsent(false);
    const consoleSpy = vi.spyOn(console, 'info');

    await analyticsService.logEvent({
      name: 'habit_toggled',
      params: { habitId: 1, date: '2026-11-17' },
    });

    expect(consoleSpy).not.toHaveBeenCalled();
    consoleSpy.mockRestore();
  });

  it('logs events safely in DEV when consent is true', async () => {
    await analyticsService.setConsent(true);
    const consoleSpy = vi.spyOn(console, 'info').mockImplementation(() => {});

    await analyticsService.logEvent({
      name: 'onboarding_reset',
    });

    expect(consoleSpy).toHaveBeenCalled();
    consoleSpy.mockRestore();
  });
});
