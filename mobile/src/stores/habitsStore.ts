import { ref } from 'vue';
import { defineStore } from 'pinia';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
import { analyticsService } from '@/services/analyticsService';
import dayjs from '@/lib/dayjs';
import type { DayInfo, Habit, StatsPeriod, SummaryDay } from '@/types';

export const useHabitStore = defineStore('habit', () => {
  const habits = ref<Habit[]>([]);
  const summary = ref<SummaryDay[]>([]);
  const dayCache = ref<Record<string, DayInfo>>({});

  const invalidateCache = () => {
    dayCache.value = {};
  };

  const createHabit = async (title: string, weekDays: number[], reminderTime: string | null) => {
    const response = await habitService.create(title, weekDays, reminderTime);
    const data = response.data;

    if (data.id && reminderTime) {
      await notificationService.scheduleHabitNotifications({
        id: data.id,
        title: data.title,
        week_days: weekDays,
        reminder_time: reminderTime,
      });
    }

    habits.value = [...habits.value, data];
    invalidateCache();

    void analyticsService.logEvent({
      name: 'habit_created',
      params: {
        weekDaysCount: weekDays.length,
        hasReminder: Boolean(reminderTime),
      },
    });

    return data;
  };

  const getDayInfo = async (date: string) => {
    const response = await habitService.getDayInfo(date);
    dayCache.value[date] = response.data;
    return response.data;
  };

  const getHabitsSummary = async (date: string | null = null) => {
    const response = await habitService.getSummary(date);
    summary.value = Array.isArray(response.data) ? response.data : [];
    return summary.value;
  };

  const fetchAllHabits = async () => {
    const response = await habitService.getAllHabits();
    habits.value = Array.isArray(response.data) ? response.data : [];
    return habits.value;
  };

  const getHabitStats = async (period: StatsPeriod | string, date: string | null = null) => {
    const response = await habitService.getStats(period, date);
    return response.data;
  };

  const getHabitDetails = async (id: number | string) => {
    const response = await habitService.getDetails(id);
    return response.data;
  };

  const updateHabit = async (id: number | string, title: string, weekDays: number[], reminder_time: string | null) => {
    const response = await habitService.update(id, title, weekDays, reminder_time);

    if (id) {
      if (reminder_time) {
        await notificationService.scheduleHabitNotifications({
          id: Number(id),
          title,
          week_days: weekDays,
          reminder_time,
        });
      } else {
        await notificationService.cancelHabitNotifications(id);
      }
    }

    const updated = response.data;
    habits.value = habits.value.map(item => (String(item.id) === String(id) ? updated : item));
    invalidateCache();

    void analyticsService.logEvent({
      name: 'habit_updated',
      params: {
        habitId: id,
        hasReminder: Boolean(reminder_time),
      },
    });

    return updated;
  };

  const applyOptimisticToggle = (habitId: number | string, targetDate: string) => {
    const currentDay = dayCache.value[targetDate];
    const previousDay: DayInfo | undefined = currentDay
      ? {
        possible_habits: [...currentDay.possible_habits],
        completed_habits: [...currentDay.completed_habits],
      }
      : undefined;

    const previousSummary = summary.value.map(item => ({ ...item }));

    let isNowCompleted = false;

    if (currentDay) {
      const alreadyCompleted = currentDay.completed_habits.some(
        h => String(h.id) === String(habitId)
      );

      if (alreadyCompleted) {
        currentDay.completed_habits = currentDay.completed_habits.filter(
          h => String(h.id) !== String(habitId)
        );
        isNowCompleted = false;
      } else {
        const habitObj = currentDay.possible_habits.find(
          h => String(h.id) === String(habitId)
        );
        if (habitObj) {
          currentDay.completed_habits = [...currentDay.completed_habits, habitObj];
          isNowCompleted = true;
        }
      }
    }

    const summaryIndex = summary.value.findIndex(item => dayjs(item.date).isSame(targetDate, 'day'));
    if (summaryIndex !== -1) {
      const delta = isNowCompleted ? 1 : -1;
      const currentItem = summary.value[summaryIndex];
      summary.value[summaryIndex] = {
        ...currentItem,
        completed: Math.max(0, currentItem.completed + delta),
      };
    } else if (isNowCompleted && currentDay) {
      summary.value.push({
        date: targetDate,
        completed: currentDay.completed_habits.length,
        total: currentDay.possible_habits.length,
      });
    }

    return () => {
      if (previousDay) {
        dayCache.value[targetDate] = previousDay;
      }
      summary.value = previousSummary;
    };
  };

  const toggleHabit = async (habitId: number | string, date: string | null = null) => {
    const targetDate = date || dayjs().format('YYYY-MM-DD');
    const rollback = applyOptimisticToggle(habitId, targetDate);

    try {
      const response = await habitService.toggle(habitId, targetDate);
      void analyticsService.logEvent({
        name: 'habit_toggled',
        params: { habitId, date: targetDate },
      });
      return response.data;
    } catch (error) {
      rollback();
      throw error;
    }
  };

  const deleteHabit = async (id: number | string) => {
    await notificationService.cancelHabitNotifications(id);
    const response = await habitService.delete(id);
    habits.value = habits.value.filter(item => String(item.id) !== String(id));
    invalidateCache();
    void analyticsService.logEvent({
      name: 'habit_deleted',
      params: { habitId: id },
    });
    return response;
  };

  return {
    habits,
    summary,
    dayCache,
    createHabit,
    getDayInfo,
    getHabitsSummary,
    fetchAllHabits,
    getHabitStats,
    getHabitDetails,
    updateHabit,
    toggleHabit,
    deleteHabit,
  };
});
