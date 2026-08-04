import api from '@/api';
import { HABIT_ENDPOINTS } from '@/constants/endpoints';
import dayjs from '@/lib/dayjs';

export const habitService = {
  create(title, weekDays, reminder_time) {
    return api.post(HABIT_ENDPOINTS.BASE, {
      title,
      week_days: weekDays,
      reminder_time,
      created_at: dayjs().format(),
    });
  },
  
  getDayInfo(date) {
    return api.get(HABIT_ENDPOINTS.DAY, {
      params: { date },
    });
  },
  
  getSummary(date = null) {
    return api.get(HABIT_ENDPOINTS.SUMMARY, {
      params: { date }
    });
  },
  
  getAllHabits() {
    return api.get(HABIT_ENDPOINTS.BASE);
  },

  getStats(period = 'W', date = null) {
    return api.get(HABIT_ENDPOINTS.STATS, {
      params: { period, date }
    });
  },
  
  getDetails(id) {
    return api.get(HABIT_ENDPOINTS.DETAILS(id));
  },
  
  update(id, title, weekDays, reminder_time) {
    return api.put(HABIT_ENDPOINTS.DETAILS(id), {
      title,
      week_days: weekDays,
      reminder_time,
    });
  },
  
  toggle(habitId, date = null) {
    return api.patch(HABIT_ENDPOINTS.TOGGLE(habitId), {
      date: date || dayjs().format('YYYY-MM-DD')
    });
  },
  
  delete(id) {
    return api.delete(HABIT_ENDPOINTS.DETAILS(id));
  }
};
