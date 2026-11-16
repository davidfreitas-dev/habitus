<script setup lang="ts">
import { computed } from 'vue';
import { useDayDimensions } from '@/composables/useDayDimensions';

const props = withDefaults(
  defineProps<{
    isCurrentDay?: boolean;
    amountOfHabits?: number;
    amountCompleted?: number;
  }>(),
  {
    isCurrentDay: false,
    amountOfHabits: 0,
    amountCompleted: 0,
  }
);

const { daySize } = useDayDimensions();

const amountAccomplishedPercentage = computed(() => {
  return props.amountOfHabits > 0
    ? Math.round((props.amountCompleted / props.amountOfHabits) * 100)
    : 0;
});

const dayClass = computed(() => {
  const percentage = amountAccomplishedPercentage.value;
  
  if (percentage === 0) return 'habit-day-0';
  if (percentage < 20)  return 'habit-day-20';
  if (percentage < 40)  return 'habit-day-40';
  if (percentage < 60)  return 'habit-day-60';
  if (percentage < 80)  return 'habit-day-80';
  return 'habit-day-100';
});
</script>

<template>
  <div
    :style="daySize"
    :class="[ dayClass, { active: isCurrentDay } ]"
  />
</template>

<style scoped>
div {
  margin: .25rem;
  border-width: 2px;
  border-style: solid;
  border-radius: .5rem;
}
div.active {
  border-width: 4px !important;
  border-color: var(--color-text-primary) !important;
}
</style>
