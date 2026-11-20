import type { Habit, HabitReminder, HabitTemplate, ReminderScheduleState } from '@/types';

export const MAX_REMINDERS_PER_DAY = 6;

export const SHORT_WEEK_DAYS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as const;

export const SHORT_DAY_NAMES = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as const;

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

export function formatHabitRemindersSummary(reminders: HabitReminder[] | undefined | null): string | null {
  if (!reminders || reminders.length === 0) return null;

  const uniqueTimes = Array.from(new Set(reminders.map((r) => r.time.substring(0, 5)))).sort();
  if (uniqueTimes.length === 0) return null;
  if (uniqueTimes.length === 1) return uniqueTimes[0];

  return `${uniqueTimes[0]} +${uniqueTimes.length - 1}`;
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

export const HABIT_TEMPLATES: readonly HabitTemplate[] = [
  {
    emoji: '💧',
    title: 'Beber 2L de água',
    weekDays: [0, 1, 2, 3, 4, 5, 6],
    reminderTimes: ['08:00', '12:00', '16:00', '20:00'],
  },
  { emoji: '🏃', title: 'Caminhada 30 min', weekDays: [1, 2, 3, 4, 5], reminderTimes: ['07:00'] },
  { emoji: '📖', title: 'Ler 15 páginas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['21:00'] },
  { emoji: '🧘', title: 'Meditar 10 min', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['08:00'] },
  { emoji: '😴', title: 'Dormir 8 horas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['22:30'] },
  { emoji: '💻', title: 'Estudar 45 min', weekDays: [1, 2, 3, 4, 5], reminderTimes: ['19:00'] },
];

export function createDefaultReminderState(): ReminderScheduleState {
  return {
    enabled: false,
    mode: 'all',
    times: [],
    perDay: {},
  };
}

/**
 * Converts a flat list of HabitReminder entries into the UI ReminderScheduleState,
 * detecting whether all active days share the same schedule ('all') or differ ('perDay').
 */
export function hydrateRemindersFromHabit(
  reminders: HabitReminder[] | undefined,
  days: number[]
): ReminderScheduleState {
  if (!reminders || reminders.length === 0) {
    return createDefaultReminderState();
  }

  const grouped: Record<number, string[]> = {};
  for (const r of reminders) {
    const day = Number(r.week_day);
    const time = r.time.substring(0, 5);
    if (!grouped[day]) grouped[day] = [];
    if (!grouped[day].includes(time)) {
      grouped[day].push(time);
    }
  }

  for (const day of Object.keys(grouped)) {
    grouped[Number(day)].sort();
  }

  const activeDays = days.length > 0 ? days : Object.keys(grouped).map(Number);
  const firstSignature = activeDays.length > 0 ? (grouped[activeDays[0]] ?? []).join(',') : '';
  const allSame =
    firstSignature.length > 0 &&
    activeDays.every((d) => (grouped[d] ?? []).join(',') === firstSignature);

  if (allSame) {
    return {
      enabled: true,
      mode: 'all',
      times: [...(grouped[activeDays[0]] ?? [])].slice(0, MAX_REMINDERS_PER_DAY),
      perDay: {},
    };
  }

  return {
    enabled: true,
    mode: 'perDay',
    times: [],
    perDay: grouped,
  };
}

/**
 * Expands ReminderScheduleState into flat HabitReminder payloads for the active week days.
 */
export function buildRemindersPayload(
  state: ReminderScheduleState,
  sortedActiveDays: number[]
): HabitReminder[] {
  if (!state.enabled) return [];

  const reminders: HabitReminder[] = [];
  for (const day of sortedActiveDays) {
    const dayTimes = state.mode === 'all' ? state.times : (state.perDay[day] ?? []);
    for (const time of dayTimes) {
      reminders.push({ week_day: day, time });
    }
  }

  return reminders;
}
