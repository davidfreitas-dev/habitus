import { ref, computed } from 'vue';
import dayjs from '@/lib/dayjs';

export function useParsedDate(dateInput: string | Date | undefined) {
  const parsedDate = ref(dayjs(dateInput).startOf('day'));
  const dayOfWeek = computed(() => parsedDate.value.format('dddd'));
  const dayAndMonth = computed(() => parsedDate.value.format('DD/MM'));
  const isDateInPast = computed(() => parsedDate.value.endOf('day').isBefore(dayjs()));

  return {
    parsedDate,
    dayOfWeek,
    dayAndMonth,
    isDateInPast
  };
}
