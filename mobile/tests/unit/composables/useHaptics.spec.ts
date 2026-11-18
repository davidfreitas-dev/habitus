import { describe, expect, it, vi, beforeEach } from 'vitest';
import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';
import { Capacitor } from '@capacitor/core';
import { useHaptics } from '@/composables/useHaptics';

vi.mock('@capacitor/haptics', () => ({
  Haptics: {
    impact: vi.fn().mockResolvedValue(undefined),
    notification: vi.fn().mockResolvedValue(undefined),
  },
  ImpactStyle: {
    Light: 'LIGHT',
    Medium: 'MEDIUM',
    Heavy: 'HEAVY',
  },
  NotificationType: {
    Success: 'SUCCESS',
    Warning: 'WARNING',
    Error: 'ERROR',
  },
}));

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn(),
  },
}));

describe('useHaptics', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('does not trigger haptic feedback on web/non-native platform', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false);

    const { triggerHaptic } = useHaptics();
    await triggerHaptic('light');
    await triggerHaptic('success');

    expect(Haptics.impact).not.toHaveBeenCalled();
    expect(Haptics.notification).not.toHaveBeenCalled();
  });

  it('triggers impact for light, medium, and heavy styles on native platform', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);

    const { triggerHaptic } = useHaptics();
    await triggerHaptic('light');
    expect(Haptics.impact).toHaveBeenCalledWith({ style: ImpactStyle.Light });

    await triggerHaptic('medium');
    expect(Haptics.impact).toHaveBeenCalledWith({ style: ImpactStyle.Medium });

    await triggerHaptic('heavy');
    expect(Haptics.impact).toHaveBeenCalledWith({ style: ImpactStyle.Heavy });
  });

  it('triggers notification for success, warning, and error types on native platform', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);

    const { triggerHaptic } = useHaptics();
    await triggerHaptic('success');
    expect(Haptics.notification).toHaveBeenCalledWith({ type: NotificationType.Success });

    await triggerHaptic('warning');
    expect(Haptics.notification).toHaveBeenCalledWith({ type: NotificationType.Warning });

    await triggerHaptic('error');
    expect(Haptics.notification).toHaveBeenCalledWith({ type: NotificationType.Error });
  });

  it('handles errors gracefully without throwing', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);
    vi.mocked(Haptics.impact).mockRejectedValueOnce(new Error('Haptics failed'));

    const { triggerHaptic } = useHaptics();
    await expect(triggerHaptic('light')).resolves.not.toThrow();
  });
});
