<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import dayjs from '@/lib/dayjs';
import { useDayDimensions } from '@/composables/useDayDimensions';
import HabitDaysStartGap from '@/components/habits/HabitDaysStartGap.vue';
import HabitDay from '@/components/habits/HabitDay.vue';
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

const router = useRouter();
const { daySize, isHorizontalLayout } = useDayDimensions();

const startWeekdayFromYear = computed(() => dayjs().startOf('year').day());

const placeholderCount = computed(() => {
  const occupiedSlots = startWeekdayFromYear.value + props.datesFromYearStart.length;
  return Math.max(0, MINIMUM_SUMMARY_DATES_SIZE - occupiedSlots);
});

const handleNavigate = (date: Date) => {
  router.push({
    name: 'Day', 
    params: {
      date: dayjs(date).format('YYYY-MM-DD')
    }
  });
};

const isCurrentDay = (date: Date) => {
  const today = dayjs().startOf('day');
  return dayjs(date).isSame(today, 'day');
};

const findDayInSummary = (date: Date) => {
  return props.summary.find(day => {
    return dayjs(date).isSame(day.date, 'day');
  });
};

const getAmount = (date: Date) => {
  const dayWithHabits = findDayInSummary(date);
  return dayWithHabits ? dayWithHabits.total : 0;
};

const getCompleted = (date: Date) => {
  const dayWithHabits = findDayInSummary(date);
  return dayWithHabits ? dayWithHabits.completed : 0;
};
</script>

<template>
  <div :class="['summary-grid', { 'summary-grid--horizontal': isHorizontalLayout }]">
    <HabitDaysStartGap />    
    <HabitDay
      v-for="(date, index) in datesFromYearStart"
      :key="index"
      :id="isCurrentDay(date) ? 'onboarding-current-day' : undefined"
      :is-current-day="isCurrentDay(date)"
      :amount-of-habits="getAmount(date)"
      :amount-completed="getCompleted(date)"
      @click="handleNavigate(date)"
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
