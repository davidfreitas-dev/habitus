import { describe, expect, it, vi, beforeEach } from 'vitest';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';
import { useNotifications } from '@/composables/useNotifications';

vi.mock('@capacitor/local-notifications', () => ({
  LocalNotifications: {
    checkPermissions: vi.fn(),
    requestPermissions: vi.fn(),
    addListener: vi.fn(),
  },
}));

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn(),
  },
}));

describe('useNotifications', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('registers localNotificationActionPerformed on native platforms', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);
    const mockCallback = vi.fn();

    vi.mocked(LocalNotifications.addListener).mockImplementation((eventName, handler) => {
      if (eventName === 'localNotificationActionPerformed') {
        // @ts-expect-error simulação de evento
        handler({ notification: { extra: { habitId: 42 } } });
      }
      return Promise.resolve({ remove: vi.fn() });
    });

    const { listenNotificationActionPerformed } = useNotifications();
    await listenNotificationActionPerformed(mockCallback);

    expect(LocalNotifications.addListener).toHaveBeenCalledWith(
      'localNotificationActionPerformed',
      expect.any(Function)
    );
    expect(mockCallback).toHaveBeenCalledWith(42);
  });

  it('does nothing on non-native platform', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false);
    const mockCallback = vi.fn();

    const { listenNotificationActionPerformed } = useNotifications();
    const handle = await listenNotificationActionPerformed(mockCallback);

    expect(handle).toBeNull();
    expect(LocalNotifications.addListener).not.toHaveBeenCalled();
  });
});
