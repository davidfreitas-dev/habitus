<script setup lang="ts">
import { computed } from 'vue';
import { useDayDimensions } from '@/composables/useDayDimensions';
import dayjs from '@/lib/dayjs';

const props = withDefaults(
  defineProps<{
    date?: Date | string;
    isCurrentDay?: boolean;
    amountOfHabits?: number;
    amountCompleted?: number;
  }>(),
  {
    date: undefined,
    isCurrentDay: false,
    amountOfHabits: 0,
    amountCompleted: 0,
  }
);

defineEmits<{
  (e: 'click'): void;
}>();

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

const ariaLabel = computed(() => {
  const dateFormatted = props.date ? dayjs(props.date).format('D [de] MMMM') : 'Dia';
  if (props.amountOfHabits === 0) {
    return `${dateFormatted}: nenhum hábito programado`;
  }
  return `${dateFormatted}: ${props.amountCompleted} de ${props.amountOfHabits} hábitos concluídos`;
});
</script>

<template>
  <div
    :style="daySize"
    :class="[ dayClass, { active: isCurrentDay } ]"
    role="button"
    tabindex="0"
    :aria-label="ariaLabel"
    @click="$emit('click')"
    @keydown.enter.prevent="$emit('click')"
    @keydown.space.prevent="$emit('click')"
  />
</template>

<style scoped>
div {
  margin: .25rem;
  border-width: 2px;
  border-style: solid;
  border-radius: .5rem;
  cursor: pointer;
}
div:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
div.active {
  border-width: 4px !important;
  border-color: var(--color-text-primary) !important;
}
</style>
