import type { Habit } from '@/types';

export const SHORT_WEEK_DAYS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as const;

export const FULL_WEEK_DAYS = [
  'Domingo',
  'Segunda-feira',
  'Terça-feira',
  'Quarta-feira',
  'Quinta-feira',
  'Sexta-feira',
  'Sábado',
] as const;

export const RECURRENCE_PRESETS = [
  { label: 'Todos os dias', days: [0, 1, 2, 3, 4, 5, 6] },
  { label: 'Dias úteis', days: [1, 2, 3, 4, 5] },
  { label: 'Fim de semana', days: [0, 6] },
] as const;

export function parseWeekDays(rawDays: number[] | string | undefined | null): number[] {
  if (!rawDays) return [];
  if (Array.isArray(rawDays)) return rawDays.map(Number);
  if (typeof rawDays === 'string' && rawDays.length > 0) {
    return rawDays
      .split(',')
      .filter(Boolean)
      .map(Number);
  }
  return [];
}

export function isHabitDayActive(habit: Pick<Habit, 'week_days'>, dayIndex: number): boolean {
  return parseWeekDays(habit.week_days).includes(dayIndex);
}

export function getFrequencySummary(habit: Pick<Habit, 'week_days'>): string {
  const days = Array.from(new Set(parseWeekDays(habit.week_days))).sort((a, b) => a - b);
  const count = days.length;

  if (count === 7) return 'Todos os dias';
  if (count === 5 && [1, 2, 3, 4, 5].every((d) => days.includes(d))) {
    return '5x na semana • Dias úteis';
  }
  if (count === 2 && [0, 6].every((d) => days.includes(d))) {
    return '2x na semana • Fim de semana';
  }
  if (count === 1) return '1x na semana';
  return `${count}x na semana`;
}

export function formatReminderTime(time: string | null | undefined): string | null {
  if (!time) return null;
  if (time.includes('T')) {
    return time.split('T')[1].substring(0, 5);
  }
  return time.substring(0, 5);
}

export function getLocalISOString(date: Date = new Date()): string {
  const offset = date.getTimezoneOffset() * 60000;
  return new Date(date.getTime() - offset).toISOString();
}

export function timeStringToLocalISO(timeStr: string): string {
  const today = new Date();
  const [hh, mm] = timeStr.split(':');
  today.setHours(Number(hh), Number(mm), 0, 0);
  return getLocalISOString(today);
}
