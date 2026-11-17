import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useHabitStore } from '@/stores/habitsStore';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
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

describe('useHabitStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('adds created habit to reactive state and schedules notifications when reminder is set', async () => {
    const store = useHabitStore();
    const createdHabit: Habit = {
      id: 10,
      title: 'Beber 2L de água',
      week_days: [1, 2, 3, 4, 5],
      reminder_time: '09:00',
    };

    vi.mocked(habitService.create).mockResolvedValue({
      data: createdHabit,
    });

    const result = await store.createHabit('Beber 2L de água', [1, 2, 3, 4, 5], '09:00');

    expect(result).toEqual(createdHabit);
    expect(store.habits).toContainEqual(createdHabit);
    expect(notificationService.scheduleHabitNotifications).toHaveBeenCalledWith({
      id: 10,
      title: 'Beber 2L de água',
      week_days: [1, 2, 3, 4, 5],
      reminder_time: '09:00',
    });
  });

  it('optimistically updates dayCache and summary on toggleHabit', async () => {
    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 1,
      title: 'Ler 20 páginas',
      week_days: [1, 2, 3],
      reminder_time: null,
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

  it('rolls back optimistic state if toggleHabit API call fails', async () => {
    const store = useHabitStore();
    const targetDate = '2026-11-17';
    const habit: Habit = {
      id: 2,
      title: 'Correr 5km',
      week_days: [2],
      reminder_time: null,
    };

    store.dayCache[targetDate] = {
      possible_habits: [habit],
      completed_habits: [],
    };
    store.summary = [
      { date: targetDate, completed: 0, total: 1 },
    ];

    vi.mocked(habitService.toggle).mockRejectedValue(new Error('Network error'));

    await expect(store.toggleHabit(2, targetDate)).rejects.toThrow('Network error');

    // Estado deve ter sido revertido (rollback)
    expect(store.dayCache[targetDate].completed_habits).toHaveLength(0);
    expect(store.summary[0].completed).toBe(0);
  });

  it('removes habit from state and cancels notifications on deleteHabit', async () => {
    const store = useHabitStore();
    store.habits = [
      { id: 5, title: 'Meditar', week_days: [0, 1], reminder_time: '07:00' },
      { id: 6, title: 'Alongar', week_days: [2, 4], reminder_time: null },
    ];

    vi.mocked(habitService.delete).mockResolvedValue({ data: null });

    await store.deleteHabit(5);

    expect(notificationService.cancelHabitNotifications).toHaveBeenCalledWith(5);
    expect(store.habits).toHaveLength(1);
    expect(store.habits[0].id).toBe(6);
  });
});
