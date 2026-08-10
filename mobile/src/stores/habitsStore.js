import { defineStore } from 'pinia';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
import { trackEvent } from '@/composables/useAnalytics';

export const useHabitStore = defineStore('habit', () => {
  const createHabit = async (title, weekDays, reminderTime) => {
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

    trackEvent('habit_created', { habit_id: data.id });

    return response.data;
  };

  const getDayInfo = async (date) => {
    const response = await habitService.getDayInfo(date);
    return response.data;
  };

  const getHabitsSummary = async (date = null) => {
    const response = await habitService.getSummary(date);
    return response.data;
  };

  const fetchAllHabits = async () => {
    const response = await habitService.getAllHabits();
    return response.data;
  };

  const getHabitStats = async (period, date = null) => {
    const response = await habitService.getStats(period, date);
    return response.data;
  };

  const getHabitDetails = async (id) => {
    const response = await habitService.getDetails(id);
    return response.data;
  };

  const updateHabit = async (id, title, weekDays, reminder_time) => {
    const response = await habitService.update(id, title, weekDays, reminder_time);
    
    if (id) {
      if (reminder_time) {
        await notificationService.scheduleHabitNotifications({
          id: id,
          title: title,
          week_days: weekDays,
          reminder_time: reminder_time,
        });
      } else {
        await notificationService.cancelHabitNotifications(id);
      }
    }

    return response.data;
  };

  const toggleHabit = async (habitId, date = null) => {
    const response = await habitService.toggle(habitId, date);
    
    // Dispara evento de conclusão/toggle
    trackEvent('habit_completed', { habit_id: habitId });
    
    // Se a API retornar dados de streak/ofensiva (ex: response.data.streak), podemos rastrear:
    if (response.data?.streak && [7, 21, 30, 100].includes(response.data.streak)) {
      trackEvent('streak_milestone', { milestone: response.data.streak, habit_id: habitId });
    }

    return response.data;
  };

  const deleteHabit = async (id) => {
    await notificationService.cancelHabitNotifications(id);
    return await habitService.delete(id);
  };

  return {
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
