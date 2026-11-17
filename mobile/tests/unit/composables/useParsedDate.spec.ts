import { describe, expect, it } from 'vitest';
import dayjs from '@/lib/dayjs';
import { useParsedDate } from '@/composables/useParsedDate';

describe('useParsedDate', () => {
  it('formats dayAndMonth and detects past dates accurately', () => {
    const pastDate = '2020-05-15';
    const { dayAndMonth, isDateInPast, isDateEditable } = useParsedDate(pastDate);

    expect(dayAndMonth.value).toBe('15/05');
    expect(isDateInPast.value).toBe(true);
    expect(isDateEditable.value).toBe(false);
  });

  it('marks today as not in the past and editable', () => {
    const today = dayjs().format('YYYY-MM-DD');
    const { isDateInPast, isDateEditable } = useParsedDate(today);

    expect(isDateInPast.value).toBe(false);
    expect(isDateEditable.value).toBe(true);
  });

  it('marks yesterday as in the past but still editable', () => {
    const yesterday = dayjs().subtract(1, 'day').format('YYYY-MM-DD');
    const { isDateInPast, isDateEditable } = useParsedDate(yesterday);

    expect(isDateInPast.value).toBe(true);
    expect(isDateEditable.value).toBe(true);
  });

  it('marks two days ago as not editable', () => {
    const twoDaysAgo = dayjs().subtract(2, 'day').format('YYYY-MM-DD');
    const { isDateInPast, isDateEditable } = useParsedDate(twoDaysAgo);

    expect(isDateInPast.value).toBe(true);
    expect(isDateEditable.value).toBe(false);
  });
});
