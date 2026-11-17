import { describe, expect, it, vi, beforeEach } from 'vitest';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';
import { notificationService } from '@/services/notificationService';

vi.mock('@capacitor/local-notifications', () => ({
  LocalNotifications: {
    createChannel: vi.fn().mockResolvedValue(undefined),
    schedule: vi.fn().mockResolvedValue(undefined),
    cancel: vi.fn().mockResolvedValue(undefined),
    getPending: vi.fn().mockResolvedValue({ notifications: [] }),
  },
}));

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn(),
  },
}));

describe('notificationService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('does nothing when platform is not native', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false);

    await notificationService.scheduleHabitNotifications({
      id: 1,
      title: 'Beber água',
      week_days: [1],
      reminder_time: '08:00',
    });

    expect(LocalNotifications.schedule).not.toHaveBeenCalled();
    expect(LocalNotifications.cancel).not.toHaveBeenCalled();
  });

  it('schedules notifications for native platform with mapped capacitor weekdays', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);

    await notificationService.scheduleHabitNotifications({
      id: 5,
      title: 'Meditar',
      week_days: [0, 3], // Sunday (0) and Wednesday (3)
      reminder_time: '07:30',
    });

    expect(LocalNotifications.cancel).toHaveBeenCalledWith({
      notifications: [
        { id: 35 }, { id: 36 }, { id: 37 }, { id: 38 }, { id: 39 }, { id: 40 }, { id: 41 },
      ],
    });

    expect(LocalNotifications.createChannel).toHaveBeenCalled();
    expect(LocalNotifications.schedule).toHaveBeenCalledTimes(1);

    const callArgs = vi.mocked(LocalNotifications.schedule).mock.calls[0][0];
    expect(callArgs.notifications).toHaveLength(2);

    // Weekday 0 mapped to Capacitor 1, id = 5 * 7 + 0 = 35
    expect(callArgs.notifications[0].id).toBe(35);
    expect(callArgs.notifications[0].schedule?.on?.weekday).toBe(1);
    expect(callArgs.notifications[0].schedule?.on?.hour).toBe(7);
    expect(callArgs.notifications[0].schedule?.on?.minute).toBe(30);
    expect(callArgs.notifications[0].extra?.habitId).toBe(5);

    // Weekday 3 mapped to Capacitor 4, id = 5 * 7 + 3 = 38
    expect(callArgs.notifications[1].id).toBe(38);
    expect(callArgs.notifications[1].schedule?.on?.weekday).toBe(4);
  });

  it('cancels all 7 possible weekday notifications for a habit id', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);

    await notificationService.cancelHabitNotifications(2);

    expect(LocalNotifications.cancel).toHaveBeenCalledWith({
      notifications: [
        { id: 14 }, { id: 15 }, { id: 16 }, { id: 17 }, { id: 18 }, { id: 19 }, { id: 20 },
      ],
    });
  });

  it('reschedules all notifications after clearing pending ones', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);
    vi.mocked(LocalNotifications.getPending).mockResolvedValue({
      notifications: [{ id: 99, title: '', body: '' }],
    });

    await notificationService.rescheduleAllNotifications([
      { id: 1, title: 'Treinar', week_days: [1], reminder_time: '18:00' },
    ]);

    expect(LocalNotifications.cancel).toHaveBeenCalledWith({
      notifications: [{ id: 99, title: '', body: '' }],
    });
    expect(LocalNotifications.schedule).toHaveBeenCalled();
  });
});
