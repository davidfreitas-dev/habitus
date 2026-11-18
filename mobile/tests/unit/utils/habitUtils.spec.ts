import { describe, expect, it } from 'vitest';
import {
  parseWeekDays,
  formatReminderTime,
  getFrequencySummary,
  isHabitDayActive,
} from '@/utils/habitUtils';

describe('habitUtils', () => {
  describe('parseWeekDays', () => {
    it('parses array of numbers or strings into numbers', () => {
      expect(parseWeekDays([0, 2, 4])).toEqual([0, 2, 4]);
      expect(parseWeekDays('1,3,5')).toEqual([1, 3, 5]);
      expect(parseWeekDays('')).toEqual([]);
      expect(parseWeekDays(undefined)).toEqual([]);
    });
  });

  describe('isHabitDayActive', () => {
    it('checks if a weekday index is active in habit', () => {
      const habit = { week_days: [1, 3, 5] };
      expect(isHabitDayActive(habit, 1)).toBe(true);
      expect(isHabitDayActive(habit, 2)).toBe(false);
    });
  });

  describe('getFrequencySummary', () => {
    it('formats human-readable frequency summary', () => {
      expect(getFrequencySummary({ week_days: [0, 1, 2, 3, 4, 5, 6] })).toBe('Todos os dias');
      expect(getFrequencySummary({ week_days: [1, 2, 3, 4, 5] })).toBe('5x na semana • Dias úteis');
      expect(getFrequencySummary({ week_days: [0, 6] })).toBe('2x na semana • Fim de semana');
      expect(getFrequencySummary({ week_days: [3] })).toBe('1x na semana');
      expect(getFrequencySummary({ week_days: [1, 3, 5] })).toBe('3x na semana');
    });
  });

  describe('formatReminderTime', () => {
    it('extracts HH:mm from ISO or time string', () => {
      expect(formatReminderTime('2026-11-18T08:30:00.000Z')).toBe('08:30');
      expect(formatReminderTime('09:15:00')).toBe('09:15');
      expect(formatReminderTime('09:15')).toBe('09:15');
      expect(formatReminderTime(null)).toBeNull();
    });
  });
});
