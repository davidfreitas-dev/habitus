import { describe, expect, it } from 'vitest';
import dayjs from '@/lib/dayjs';
import { useGenerateRange } from '@/composables/useGenerateRange';

describe('useGenerateRange', () => {
  it('generates a contiguous list of dates from Jan 1st up to today', () => {
    const { generateDatesFromYearBeginning } = useGenerateRange();
    const dates = generateDatesFromYearBeginning();

    expect(dates.length).toBeGreaterThan(0);

    const firstDate = dayjs(dates[0]);
    const lastDate = dayjs(dates[dates.length - 1]);
    const today = dayjs().startOf('day');

    expect(firstDate.month()).toBe(0); // Janeiro (0-indexed)
    expect(firstDate.date()).toBe(1);
    expect(lastDate.isSame(today, 'day')).toBe(true);
  });
});
