import { describe, expect, test } from 'vitest';
import {
  buildRemindersPayload,
  createDefaultReminderState,
  hydrateRemindersFromHabit,
} from '@/utils/habitUtils';

describe('habitUtils reminder helpers', () => {
  test('hydrateRemindersFromHabit returns default disabled state when empty', () => {
    expect(hydrateRemindersFromHabit([], [1, 2])).toEqual(createDefaultReminderState());
    expect(hydrateRemindersFromHabit(undefined, [1, 2])).toEqual(createDefaultReminderState());
  });

  test('hydrateRemindersFromHabit detects uniform schedule as mode "all"', () => {
    const state = hydrateRemindersFromHabit(
      [
        { week_day: 1, time: '09:00:00' },
        { week_day: 1, time: '18:00' },
        { week_day: 3, time: '18:00' },
        { week_day: 3, time: '09:00' },
      ],
      [1, 3]
    );

    expect(state).toEqual({
      enabled: true,
      mode: 'all',
      times: ['09:00', '18:00'],
      perDay: {},
    });
  });

  test('hydrateRemindersFromHabit detects divergent schedule as mode "perDay"', () => {
    const state = hydrateRemindersFromHabit(
      [
        { week_day: 1, time: '09:00' },
        { week_day: 3, time: '10:30' },
      ],
      [1, 3]
    );

    expect(state).toEqual({
      enabled: true,
      mode: 'perDay',
      times: [],
      perDay: {
        1: ['09:00'],
        3: ['10:30'],
      },
    });
  });

  test('buildRemindersPayload returns empty array when disabled', () => {
    const state = {
      enabled: false,
      mode: 'all' as const,
      times: ['09:00'],
      perDay: {},
    };
    expect(buildRemindersPayload(state, [1, 2])).toEqual([]);
  });

  test('buildRemindersPayload expands "all" and "perDay" modes for sorted active days', () => {
    expect(
      buildRemindersPayload(
        {
          enabled: true,
          mode: 'all',
          times: ['08:00', '20:00'],
          perDay: {},
        },
        [1, 5]
      )
    ).toEqual([
      { week_day: 1, time: '08:00' },
      { week_day: 1, time: '20:00' },
      { week_day: 5, time: '08:00' },
      { week_day: 5, time: '20:00' },
    ]);

    expect(
      buildRemindersPayload(
        {
          enabled: true,
          mode: 'perDay',
          times: [],
          perDay: { 1: ['07:30'], 5: ['19:00'] },
        },
        [1, 5]
      )
    ).toEqual([
      { week_day: 1, time: '07:30' },
      { week_day: 5, time: '19:00' },
    ]);
  });
});
