<script setup lang="ts">
import { computed } from 'vue';
import dayjs from '@/lib/dayjs';
import { useDayDimensions } from '@/composables/useDayDimensions';
import HabitDaysStartGap from '@/components/features/habits/HabitDaysStartGap.vue';
import HabitDay from '@/components/features/habits/HabitDay.vue';
import type { SummaryDay } from '@/types';

const MINIMUM_SUMMARY_DATES_SIZE = 18 * 7;

const props = withDefaults(
  defineProps<{
    summary?: SummaryDay[];
    datesFromYearStart?: Date[];
  }>(),
  {
    summary: () => [],
    datesFromYearStart: () => [],
  }
);

const emit = defineEmits<{
  (e: 'selectDay', date: Date): void;
}>();

const { daySize, isHorizontalLayout } = useDayDimensions();

const startWeekdayFromYear = computed(() => dayjs().startOf('year').day());

const placeholderCount = computed(() => {
  const occupiedSlots = startWeekdayFromYear.value + props.datesFromYearStart.length;
  return Math.max(0, MINIMUM_SUMMARY_DATES_SIZE - occupiedSlots);
});

const summaryMap = computed(() => {
  const map = new Map<string, SummaryDay>();
  for (const item of props.summary) {
    const key = item.date.length === 10 ? item.date : dayjs(item.date).format('YYYY-MM-DD');
    map.set(key, item);
  }
  return map;
});

const todayKey = computed(() => dayjs().format('YYYY-MM-DD'));

const enrichedDays = computed(() => {
  const map = summaryMap.value;
  const today = todayKey.value;

  return props.datesFromYearStart.map((date) => {
    const key = dayjs(date).format('YYYY-MM-DD');
    const summaryItem = map.get(key);
    return {
      date,
      key,
      isCurrentDay: key === today,
      amountOfHabits: summaryItem ? summaryItem.total : 0,
      amountCompleted: summaryItem ? summaryItem.completed : 0,
    };
  });
});

const handleNavigate = (date: Date) => {
  emit('selectDay', date);
};
</script>

<template>
  <div :class="['summary-grid', { 'summary-grid--horizontal': isHorizontalLayout }]">
    <HabitDaysStartGap />
    <HabitDay
      v-for="item in enrichedDays"
      :id="item.isCurrentDay ? 'onboarding-current-day' : undefined"
      :key="item.key"
      :is-current-day="item.isCurrentDay"
      :date="item.date"
      :amount-of-habits="item.amountOfHabits"
      :amount-completed="item.amountCompleted"
      @click="handleNavigate(item.date)"
    />
    <template v-if="isHorizontalLayout && placeholderCount > 0">
      <div
        v-for="n in placeholderCount"
        :key="`placeholder-${n}`"
        :style="daySize"
        class="habit-day-placeholder"
      />
    </template>
  </div>
</template>

<style scoped>
.summary-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  justify-items: center;
}

.summary-grid--horizontal {
  grid-template-columns: unset;
  grid-template-rows: repeat(7, auto);
  grid-auto-flow: column;
  width: max-content;
}

.habit-day-placeholder {
  margin: .25rem;
  border-width: 2px;
  border-style: solid;
  border-radius: .5rem;
  background: var(--habit-bg-0);
  border-color: var(--habit-border-0);
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}
</style>
