import { ref } from 'vue';
import { defineStore } from 'pinia';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
import { analyticsService } from '@/services/analyticsService';
import { syncQueueService } from '@/services/syncQueueService';
import { checkIsOnline, isNetworkError } from '@/services/networkUtils';
import dayjs from '@/lib/dayjs';
import type { DayInfo, Habit, StatsPeriod, SummaryDay } from '@/types';

export const useHabitStore = defineStore(
  'habit',
  () => {
    const habits = ref<Habit[]>([]);
    const summary = ref<SummaryDay[]>([]);
    const dayCache = ref<Record<string, DayInfo>>({});
    const isSyncing = ref(false);
    const pendingSyncCount = ref(0);

    const initPendingSyncCount = async () => {
      const count = await syncQueueService.getCount();
      pendingSyncCount.value = count;
      return count;
    };

    // Auto-initialize count on load
    void initPendingSyncCount();

    const invalidateCache = () => {
      dayCache.value = {};
    };

    const clearHabitsState = () => {
      habits.value = [];
      summary.value = [];
      dayCache.value = {};
      pendingSyncCount.value = 0;
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
      try {
        const response = await habitService.getDayInfo(date);
        dayCache.value[date] = response.data;
        return response.data;
      } catch (error) {
        if (dayCache.value[date] && isNetworkError(error)) {
          return dayCache.value[date];
        }
        throw error;
      }
    };

    const getHabitsSummary = async (date: string | null = null) => {
      try {
        const response = await habitService.getSummary(date);
        summary.value = Array.isArray(response.data) ? response.data : [];
        return summary.value;
      } catch (error) {
        if (summary.value.length > 0 && isNetworkError(error)) {
          return summary.value;
        }
        throw error;
      }
    };

    const fetchAllHabits = async () => {
      try {
        const response = await habitService.getAllHabits();
        habits.value = Array.isArray(response.data) ? response.data : [];
        return habits.value;
      } catch (error) {
        if (habits.value.length > 0 && isNetworkError(error)) {
          return habits.value;
        }
        throw error;
      }
    };

    const getHabitStats = async (period: StatsPeriod | string, date: string | null = null) => {
      const response = await habitService.getStats(period, date);
      return response.data;
    };

    const getHabitDetails = async (id: number | string) => {
      const response = await habitService.getDetails(id);
      return response.data;
    };

    const updateHabit = async (
      id: number | string,
      title: string,
      weekDays: number[],
      reminder_time: string | null
    ) => {
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

    const syncPendingHabits = async (): Promise<{ synced: number; failed: number }> => {
      if (isSyncing.value) {
        return { synced: 0, failed: 0 };
      }

      const queue = await syncQueueService.getQueue();
      if (queue.length === 0) {
        pendingSyncCount.value = 0;
        return { synced: 0, failed: 0 };
      }

      isSyncing.value = true;
      let syncedCount = 0;
      let failedCount = 0;
      const affectedDates = new Set<string>();

      try {
        for (const item of queue) {
          if (item.type === 'TOGGLE_HABIT') {
            try {
              await habitService.toggle(item.habitId, item.date);
              await syncQueueService.remove(item.id);
              affectedDates.add(item.date);
              syncedCount++;
            } catch (error) {
              if (isNetworkError(error)) {
                // Network error: stop processing remaining items to retry later
                break;
              }
              // Permanent failure (e.g. 404 habit deleted or 400 uneditable date): remove to unblock queue
              await syncQueueService.remove(item.id);
              failedCount++;
            }
          }
        }

        pendingSyncCount.value = await syncQueueService.getCount();

        if (syncedCount > 0) {
          try {
            await getHabitsSummary();
            for (const d of affectedDates) {
              if (dayCache.value[d]) {
                await getDayInfo(d);
              }
            }
          } catch (error) {
            console.error('Error refreshing summary after sync:', error);
          }

          void analyticsService.logEvent({
            name: 'offline_sync_completed',
            params: { syncedCount, failedCount },
          });
        }
      } finally {
        isSyncing.value = false;
      }

      return { synced: syncedCount, failed: failedCount };
    };

    const toggleHabit = async (habitId: number | string, date: string | null = null) => {
      const targetDate = date || dayjs().format('YYYY-MM-DD');
      const rollback = applyOptimisticToggle(habitId, targetDate);

      const isOnline = await checkIsOnline();

      if (!isOnline) {
        const { queue } = await syncQueueService.enqueueToggle(habitId, targetDate);
        pendingSyncCount.value = queue.length;
        void analyticsService.logEvent({
          name: 'habit_toggled_offline',
          params: { habitId, date: targetDate },
        });
        return { queued: true };
      }

      // If there are already items waiting in the sync queue, enqueue this toggle too to preserve FIFO
      if (pendingSyncCount.value > 0) {
        const { queue } = await syncQueueService.enqueueToggle(habitId, targetDate);
        pendingSyncCount.value = queue.length;
        void syncPendingHabits();
        return { queued: true };
      }

      try {
        const response = await habitService.toggle(habitId, targetDate);
        void analyticsService.logEvent({
          name: 'habit_toggled',
          params: { habitId, date: targetDate },
        });
        return response.data;
      } catch (error) {
        // Sudden network loss during the request: enqueue instead of rolling back
        if (isNetworkError(error)) {
          const { queue } = await syncQueueService.enqueueToggle(habitId, targetDate);
          pendingSyncCount.value = queue.length;
          void analyticsService.logEvent({
            name: 'habit_toggled_offline_fallback',
            params: { habitId, date: targetDate },
          });
          return { queued: true };
        }

        // Server validation/business error: rollback optimistic state
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
      isSyncing,
      pendingSyncCount,
      initPendingSyncCount,
      clearHabitsState,
      syncPendingHabits,
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
  },
  {
    persist: {
      pick: ['habits', 'summary', 'dayCache'],
    },
  }
);
