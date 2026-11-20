import { nextTick, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import dayjs from '@/lib/dayjs';

/**
 * Synchronizes the day sheet modal state with route query parameters
 * (`?action=today` or `?date=YYYY-MM-DD`) and cleans up consumed query params.
 */
export function useDaySheetRoute() {
  const route = useRoute();
  const router = useRouter();

  const isDaySheetOpen = ref(false);
  const selectedDate = ref<Date | null>(null);

  const clearRouteActions = () => {
    if (route.query.action || route.query.date || route.query.habitId) {
      const restQuery = { ...route.query };
      delete restQuery.action;
      delete restQuery.date;
      delete restQuery.habitId;
      void router.replace({ path: route.path, query: restQuery });
    }
  };

  watch(
    () => [route.query.action, route.query.date],
    ([action, queryDate]) => {
      let shouldOpen = false;

      if (action === 'today') {
        selectedDate.value = new Date();
        shouldOpen = true;
      } else if (queryDate && typeof queryDate === 'string') {
        const parsed = dayjs(queryDate).toDate();
        if (!isNaN(parsed.getTime())) {
          selectedDate.value = parsed;
          shouldOpen = true;
        }
      }

      if (shouldOpen) {
        isDaySheetOpen.value = true;
        void nextTick(() => {
          clearRouteActions();
        });
      }
    },
    { immediate: true }
  );

  watch(isDaySheetOpen, (isOpen) => {
    if (!isOpen) {
      clearRouteActions();
    }
  });

  const openDaySheet = (date: Date) => {
    selectedDate.value = date;
    isDaySheetOpen.value = true;
  };

  return {
    isDaySheetOpen,
    selectedDate,
    openDaySheet,
    clearRouteActions,
  };
}
