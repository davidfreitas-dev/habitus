<template>
  <div :class="['week-days', `week-days--${direction}`]">
    <span
      v-for="(weekDay, index) in SHORT_WEEK_DAYS"
      :key="index"
      :style="itemStyle"
    >
      {{ weekDay }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDayDimensions } from '@/composables/useDayDimensions';
import { SHORT_WEEK_DAYS } from '@/utils/habitUtils';

const props = withDefaults(
  defineProps<{
    direction?: 'horizontal' | 'vertical';
  }>(),
  {
    direction: 'horizontal',
  }
);

const WEEK_DAYS_COUNT = SHORT_WEEK_DAYS.length;
const { daySize: squareSize } = useDayDimensions();

const itemStyle = computed(() => {
  if (props.direction === 'vertical') {
    return {
      height: squareSize.value.height,
    };
  }

  const size = 100 / WEEK_DAYS_COUNT;
  return { width: size + '%', height: size + '%' };
});
</script>

<style scoped>
.week-days {
  display: flex;
  background: var(--color-background-primary);
}

.week-days--horizontal {
  position: sticky;
  top: 0;
  z-index: 1;
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  padding: 0 1rem;
  padding-bottom: .5rem;
}

.week-days--vertical {
  display: grid;
  grid-template-rows: repeat(7, auto);
  padding-right: .75rem;
  flex-shrink: 0;
}

.week-days span {
  color: var(--color-text-secondary);
  font-size: 1.25rem;
  font-weight: 700;
  text-align: center;
  margin-left: .25rem;
  margin-right: .25rem;
}

.week-days--vertical span {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: .25rem;
}
</style>
