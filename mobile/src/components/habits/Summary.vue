<script setup lang="ts">
import { useRouter } from 'vue-router';
import dayjs from '@/lib/dayjs';
import HabitDaysStartGap from '@/components/habits/HabitDaysStartGap.vue';
import HabitDay from '@/components/habits/HabitDay.vue';
import type { SummaryDay } from '@/types';

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
  <div>
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
  </div>
</template>

<style scoped>
div {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  justify-items: center;
}
</style>
