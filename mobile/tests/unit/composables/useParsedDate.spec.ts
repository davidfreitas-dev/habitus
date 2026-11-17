import { describe, expect, it } from 'vitest';
import dayjs from '@/lib/dayjs';
import { useParsedDate } from '@/composables/useParsedDate';

describe('useParsedDate', () => {
  it('formats dayAndMonth and detects past dates accurately', () => {
    const pastDate = '2020-05-15';
    const { dayAndMonth, isDateInPast } = useParsedDate(pastDate);

    expect(dayAndMonth.value).toBe('15/05');
    expect(isDateInPast.value).toBe(true);
  });

  it('marks today as not in the past', () => {
    const today = dayjs().format('YYYY-MM-DD');
    const { isDateInPast } = useParsedDate(today);

    expect(isDateInPast.value).toBe(false);
  });
});
