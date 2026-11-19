import api from '@/api';
import { HABIT_ENDPOINTS } from '@/constants/endpoints';
import dayjs from '@/lib/dayjs';
import type { ApiResponse, DayInfo, Habit, HabitReminder, HabitStats, StatsPeriod, SummaryDay } from '@/types';

export const habitService = {
  create(title: string, weekDays: number[], reminders: HabitReminder[]) {
    return api.post<ApiResponse<Habit>>(HABIT_ENDPOINTS.BASE, {
      title,
      week_days: weekDays,
      reminders,
      created_at: dayjs().format(),
    });
  },
  
  getDayInfo(date: string) {
    return api.get<ApiResponse<DayInfo>>(HABIT_ENDPOINTS.DAY, {
      params: { date },
    });
  },
  
  getSummary(date: string | null = null) {
    return api.get<ApiResponse<SummaryDay[]>>(HABIT_ENDPOINTS.SUMMARY, {
      params: { date }
    });
  },
  
  getAllHabits() {
    return api.get<ApiResponse<Habit[]>>(HABIT_ENDPOINTS.BASE);
  },

  getStats(period: StatsPeriod | string = 'W', date: string | null = null) {
    return api.get<ApiResponse<HabitStats>>(HABIT_ENDPOINTS.STATS, {
      params: { period, date }
    });
  },
  
  getDetails(id: number | string) {
    return api.get<ApiResponse<Habit>>(HABIT_ENDPOINTS.DETAILS(id));
  },
  
  update(id: number | string, title: string, weekDays: number[], reminders: HabitReminder[]) {
    return api.put<ApiResponse<Habit>>(HABIT_ENDPOINTS.DETAILS(id), {
      title,
      week_days: weekDays,
      reminders,
    });
  },
  
  toggle(habitId: number | string, date: string | null = null) {
    return api.patch<ApiResponse<{ completed: boolean }>>(HABIT_ENDPOINTS.TOGGLE(habitId), {
      date: date || dayjs().format('YYYY-MM-DD')
    });
  },
  
  delete(id: number | string) {
    return api.delete<ApiResponse<null>>(HABIT_ENDPOINTS.DETAILS(id));
  }
};
