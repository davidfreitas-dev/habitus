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
    vi.mocked(LocalNotifications.getPending).mockResolvedValue({ notifications: [] });
  });

  it('does nothing when platform is not native', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false);

    await notificationService.scheduleHabitNotifications({
      id: 1,
      title: 'Beber água',
      week_days: [1],
      reminders: [{ week_day: 1, time: '08:00' }],
    });

    expect(LocalNotifications.schedule).not.toHaveBeenCalled();
    expect(LocalNotifications.cancel).not.toHaveBeenCalled();
  });

  it('schedules multiple notifications per day and per-day times with unique ids', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);

    await notificationService.scheduleHabitNotifications({
      id: 5,
      title: 'Beber 2L de água',
      week_days: [0, 3], // Sunday (0) and Wednesday (3)
      reminders: [
        { week_day: 0, time: '07:30' },
        { week_day: 0, time: '14:00' },
        { week_day: 3, time: '09:15' },
      ],
    });

    expect(LocalNotifications.createChannel).toHaveBeenCalled();
    expect(LocalNotifications.schedule).toHaveBeenCalledTimes(1);

    const callArgs = vi.mocked(LocalNotifications.schedule).mock.calls[0][0];
    expect(callArgs.notifications).toHaveLength(3);

    const ids = callArgs.notifications.map((n) => n.id);
    expect(new Set(ids).size).toBe(3);

    expect(callArgs.notifications[0].schedule?.on?.weekday).toBe(1); // Sunday -> 1
    expect(callArgs.notifications[0].schedule?.on?.hour).toBe(7);
    expect(callArgs.notifications[0].schedule?.on?.minute).toBe(30);
    expect(callArgs.notifications[0].extra?.habitId).toBe(5);

    expect(callArgs.notifications[1].schedule?.on?.weekday).toBe(1);
    expect(callArgs.notifications[1].schedule?.on?.hour).toBe(14);
    expect(callArgs.notifications[1].schedule?.on?.minute).toBe(0);

    expect(callArgs.notifications[2].schedule?.on?.weekday).toBe(4); // Wednesday -> 4
    expect(callArgs.notifications[2].schedule?.on?.hour).toBe(9);
    expect(callArgs.notifications[2].schedule?.on?.minute).toBe(15);
  });

  it('cancels only pending notifications matching habitId', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);
    vi.mocked(LocalNotifications.getPending).mockResolvedValue({
      notifications: [
        { id: 101, title: '', body: '', extra: { habitId: 2 } },
        { id: 102, title: '', body: '', extra: { habitId: 2 } },
        { id: 201, title: '', body: '', extra: { habitId: 9 } },
      ],
    });

    await notificationService.cancelHabitNotifications(2);

    expect(LocalNotifications.cancel).toHaveBeenCalledWith({
      notifications: [{ id: 101 }, { id: 102 }],
    });
  });

  it('reschedules all notifications after clearing pending ones', async () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true);
    vi.mocked(LocalNotifications.getPending).mockResolvedValue({
      notifications: [{ id: 99, title: '', body: '' }],
    });

    await notificationService.rescheduleAllNotifications([
      { id: 1, title: 'Treinar', week_days: [1], reminders: [{ week_day: 1, time: '18:00' }] },
    ]);

    expect(LocalNotifications.cancel).toHaveBeenCalledWith({
      notifications: [{ id: 99, title: '', body: '' }],
    });
    expect(LocalNotifications.schedule).toHaveBeenCalled();
  });
});
