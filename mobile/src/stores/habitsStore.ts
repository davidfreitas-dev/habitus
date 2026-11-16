import { defineStore } from 'pinia';
import { habitService } from '@/services/habitService';
import { notificationService } from '@/services/notificationService';
import type { StatsPeriod } from '@/types';

export const useHabitStore = defineStore('habit', () => {
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

    return response.data;
  };

  const getDayInfo = async (date: string) => {
    const response = await habitService.getDayInfo(date);
    return response.data;
  };

  const getHabitsSummary = async (date: string | null = null) => {
    const response = await habitService.getSummary(date);
    return response.data;
  };

  const fetchAllHabits = async () => {
    const response = await habitService.getAllHabits();
    return response.data;
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

  const toggleHabit = async (habitId: number | string, date: string | null = null) => {
    const response = await habitService.toggle(habitId, date);
    return response.data;
  };

  const deleteHabit = async (id: number | string) => {
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
