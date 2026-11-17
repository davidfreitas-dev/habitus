import { computed, unref, type MaybeRefOrGetter } from 'vue';
import dayjs from '@/lib/dayjs';

export function useParsedDate(dateInput: MaybeRefOrGetter<string | Date | undefined>) {
  const parsedDate = computed(() => {
    const raw = typeof dateInput === 'function' ? dateInput() : unref(dateInput);
    return dayjs(raw).startOf('day');
  });

  const dayOfWeek = computed(() => parsedDate.value.format('dddd'));
  const dayAndMonth = computed(() => parsedDate.value.format('DD/MM'));
  const isDateInPast = computed(() => parsedDate.value.endOf('day').isBefore(dayjs().startOf('day')));

  const isDateEditable = computed(() => {
    const today = dayjs().startOf('day');
    const yesterday = today.subtract(1, 'day');
    return parsedDate.value.isSame(today, 'day') || parsedDate.value.isSame(yesterday, 'day');
  });

  return {
    parsedDate,
    dayOfWeek,
    dayAndMonth,
    isDateInPast,
    isDateEditable,
  };
}
