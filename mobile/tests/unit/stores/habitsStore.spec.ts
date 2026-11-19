import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useHabitStore } from '@/stores/habitsStore';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
import { syncQueueService } from '@/services/syncQueueService';
import { checkIsOnline } from '@/services/networkUtils';
import type { Habit } from '@/types';

vi.mock('@/services/habitService', () => ({
  habitService: {
    create: vi.fn(),
    getDayInfo: vi.fn(),
    getSummary: vi.fn(),
    getAllHabits: vi.fn(),
    getStats: vi.fn(),
    getDetails: vi.fn(),
    update: vi.fn(),
    toggle: vi.fn(),
    delete: vi.fn(),
  },
}));

vi.mock('@/services/notificationService', () => ({
  notificationService: {
    scheduleHabitNotifications: vi.fn(),
    cancelHabitNotifications: vi.fn(),
    rescheduleAllNotifications: vi.fn(),
  },
}));

vi.mock('@/services/analyticsService', () => ({
  analyticsService: {
    logEvent: vi.fn().mockResolvedValue(undefined),
    getConsent: vi.fn().mockResolvedValue(true),
    setConsent: vi.fn().mockResolvedValue(undefined),
  },
}));

vi.mock('@/services/networkUtils', () => ({
  isNetworkError: vi.fn((err: unknown) => {
    if (!err || typeof err !== 'object') return false;
    const e = err as { response?: unknown; message?: string };
    if (e.response) return false;
    return Boolean(e.message?.toLowerCase().includes('network'));
  }),
  checkIsOnline: vi.fn().mockResolvedValue(true),
}));

vi.mock('@/services/syncQueueService', () => ({
  syncQueueService: {
    getQueue: vi.fn().mockResolvedValue([]),
    saveQueue: vi.fn().mockResolvedValue(undefined),
    enqueueToggle: vi.fn().mockResolvedValue({ enqueued: true, queue: [{ id: '1' }] }),
    remove: vi.fn().mockResolvedValue([]),
    clear: vi.fn().mockResolvedValue(undefined),
    getCount: vi.fn().mockResolvedValue(0),
  },
}));

describe('useHabitStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('adds created habit to reactive state and schedules notifications when reminder is set', async () => {
    const store = useHabitStore();
    const reminders = [
      { week_day: 1, time: '09:00' },
      { week_day: 1, time: '15:00' },
    ];
    const createdHabit: Habit = {
      id: 10,
      title: 'Beber 2L de água',
      week_days: [1, 2, 3, 4, 5],
      reminders,
    };

    vi.mocked(habitService.create).mockResolvedValue({
      data: createdHabit,
    });

    const result = await store.createHabit('Beber 2L de água', [1, 2, 3, 4, 5], reminders);

    expect(result).toEqual(createdHabit);
    expect(store.habits).toContainEqual(createdHabit);
    expect(notificationService.scheduleHabitNotifications).toHaveBeenCalledWith({
      id: 10,
      title: 'Beber 2L de água',
      week_days: [1, 2, 3, 4, 5],
      reminders,
    });
  });

  it('optimistically updates dayCache and summary on toggleHabit', async () => {
    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 1,
      title: 'Ler 20 páginas',
      week_days: [1, 2, 3],
      reminders: [],
    };

    store.dayCache[targetDate] = {
      possible_habits: [habit],
      completed_habits: [],
    };
    store.summary = [
      { date: targetDate, completed: 0, total: 1 },
    ];

    let resolveApiCall!: (value: { data: { completed: boolean } }) => void;
    const apiPromise = new Promise<{ data: { completed: boolean } }>((resolve) => {
      resolveApiCall = resolve;
    });

    vi.mocked(habitService.toggle).mockReturnValue(apiPromise);

    const togglePromise = store.toggleHabit(1, targetDate);

    // Verifica que a UI otimista já atualizou ANTES da resposta da API
    expect(store.dayCache[targetDate].completed_habits).toHaveLength(1);
    expect(store.summary[0].completed).toBe(1);

    resolveApiCall({ data: { completed: true } });
    await togglePromise;

    expect(store.dayCache[targetDate].completed_habits).toHaveLength(1);
    expect(store.summary[0].completed).toBe(1);
  });

  it('rolls back optimistic state if toggleHabit server API call fails with business error', async () => {
    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 2,
      title: 'Correr 5km',
      week_days: [2],
      reminders: [],
    };

    store.dayCache[targetDate] = {
      possible_habits: [habit],
      completed_habits: [],
    };
    store.summary = [
      { date: targetDate, completed: 0, total: 1 },
    ];

    const serverError = {
      response: { status: 400, data: { message: 'Invalid date' } },
      message: 'Request failed with status code 400',
    };
    vi.mocked(habitService.toggle).mockRejectedValue(serverError);

    await expect(store.toggleHabit(2, targetDate)).rejects.toMatchObject({
      response: { status: 400 },
    });

    // Estado deve ter sido revertido (rollback)
    expect(store.dayCache[targetDate].completed_habits).toHaveLength(0);
    expect(store.summary[0].completed).toBe(0);
  });

  it('enqueues toggle offline without rollback when network is disconnected', async () => {
    vi.mocked(checkIsOnline).mockResolvedValue(false);
    vi.mocked(syncQueueService.enqueueToggle).mockResolvedValue({
      enqueued: true,
      queue: [{ id: 'item_1', type: 'TOGGLE_HABIT', habitId: 3, date: '2026-11-17', timestamp: 123 }],
    });

    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 3,
      title: 'Dormir cedo',
      week_days: [1, 2],
      reminders: [],
    };

    store.dayCache[targetDate] = {
      possible_habits: [habit],
      completed_habits: [],
    };
    store.summary = [{ date: targetDate, completed: 0, total: 1 }];

    const result = await store.toggleHabit(3, targetDate);

    // Deve retornar queued sem lançar exceção
    expect(result).toEqual({ queued: true });
    // Não deve chamar habitService.toggle
    expect(habitService.toggle).not.toHaveBeenCalled();
    // Enfileirou na syncQueueService
    expect(syncQueueService.enqueueToggle).toHaveBeenCalledWith(3, targetDate);
    // UI otimista mantida (sem rollback)
    expect(store.dayCache[targetDate].completed_habits).toHaveLength(1);
    expect(store.summary[0].completed).toBe(1);
    expect(store.pendingSyncCount).toBe(1);
  });

  it('enqueues toggle offline fallback without rollback when API request fails with network error', async () => {
    vi.mocked(checkIsOnline).mockResolvedValue(true);
    vi.mocked(habitService.toggle).mockRejectedValue(new Error('Network error: connection lost'));
    vi.mocked(syncQueueService.enqueueToggle).mockResolvedValue({
      enqueued: true,
      queue: [{ id: 'item_2', type: 'TOGGLE_HABIT', habitId: 4, date: '2026-11-17', timestamp: 124 }],
    });

    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 4,
      title: 'Beber Chá',
      week_days: [1, 2],
      reminders: [],
    };

    store.dayCache[targetDate] = {
      possible_habits: [habit],
      completed_habits: [],
    };
    store.summary = [{ date: targetDate, completed: 0, total: 1 }];

    const result = await store.toggleHabit(4, targetDate);

    expect(result).toEqual({ queued: true });
    expect(syncQueueService.enqueueToggle).toHaveBeenCalledWith(4, targetDate);
    // UI otimista preservada
    expect(store.dayCache[targetDate].completed_habits).toHaveLength(1);
    expect(store.summary[0].completed).toBe(1);
  });

  it('syncs pending habits sequentially and refreshes summary', async () => {
    const queueItems = [
      { id: 'item_1', type: 'TOGGLE_HABIT' as const, habitId: 10, date: '2026-11-17', timestamp: 100 },
      { id: 'item_2', type: 'TOGGLE_HABIT' as const, habitId: 20, date: '2026-11-17', timestamp: 200 },
    ];
    vi.mocked(syncQueueService.getQueue).mockResolvedValue(queueItems);
    vi.mocked(habitService.toggle).mockResolvedValue({ data: null });
    vi.mocked(habitService.getSummary).mockResolvedValue({ data: [] });
    vi.mocked(syncQueueService.getCount).mockResolvedValue(0);

    const store = useHabitStore();
    const result = await store.syncPendingHabits();

    expect(result.synced).toBe(2);
    expect(result.failed).toBe(0);
    expect(habitService.toggle).toHaveBeenCalledWith(10, '2026-11-17');
    expect(habitService.toggle).toHaveBeenCalledWith(20, '2026-11-17');
    expect(syncQueueService.remove).toHaveBeenCalledWith('item_1');
    expect(syncQueueService.remove).toHaveBeenCalledWith('item_2');
    expect(habitService.getSummary).toHaveBeenCalled();
  });

  it('removes habit from state and cancels notifications on deleteHabit', async () => {
    const store = useHabitStore();
    store.habits = [
      { id: 5, title: 'Meditar', week_days: [0, 1], reminders: [{ week_day: 0, time: '07:00' }] },
      { id: 6, title: 'Alongar', week_days: [2, 4], reminders: [] },
    ];

    vi.mocked(habitService.delete).mockResolvedValue({ data: null });

    await store.deleteHabit(5);

    expect(notificationService.cancelHabitNotifications).toHaveBeenCalledWith(5);
    expect(store.habits).toHaveLength(1);
    expect(store.habits[0].id).toBe(6);
  });
});
