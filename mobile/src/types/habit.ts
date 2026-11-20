export interface HabitReminder {
  week_day: number;
  time: string; // "HH:mm"
}

export type ReminderMode = 'all' | 'perDay';

export interface ReminderScheduleState {
  enabled: boolean;
  mode: ReminderMode;
  times: string[];
  perDay: Record<number, string[]>;
}

export interface HabitTemplate {
  title: string;
  emoji: string;
  weekDays: number[];
  reminderTimes: string[];
}

export interface Habit {
  id: number;
  title: string;
  week_days: number[];
  user_id?: number;
  reminders: HabitReminder[];
  created_at?: string;
  updated_at?: string;
}

export interface HabitDraft {
  id?: number | string | null;
  title: string;
  week_days: number[] | string;
  reminders: HabitReminder[];
}

export interface HabitFormSubmitPayload {
  title: string;
  weekDays: number[];
  reminderEnabled: boolean;
  reminders: HabitReminder[];
}

export interface DayInfo {
  possible_habits: Habit[];
  completed_habits: Habit[];
}

export interface SummaryDay {
  date: string;
  completed: number;
  total: number;
}

export type StatsPeriod = 'W' | 'M' | '3M' | '6M' | 'Y';

export interface DailyStat {
  week_day: number;
  label: string;
  percentage: number | null;
  completed: number;
  total: number;
}

export interface HabitStats {
  daily_stats: DailyStat[];
  current_streak: number;
  longest_streak: number;
}
