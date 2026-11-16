<template>
  <div>
    <span
      v-for="(weekDay, index) in weekDays"
      :key="index"
      :style="daySize"
    >
      {{ weekDay }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const weekDays = ref(['D', 'S', 'T', 'Q', 'Q', 'S', 'S']);

const WEEK_DAYS_COUNT = 7;

const daySize = computed(() => {
  const size = 100 / WEEK_DAYS_COUNT;  
  return { width: size + '%', height: size + '%' };
});
</script>

<style scoped>
div {
  display: flex;
  position: sticky;
  top: 0;
  z-index: 1;
  background: var(--color-background-primary);
  padding: 0 1rem;
  padding-bottom: .5rem;
}
div span {
  color: var(--color-text-secondary);
  font-size: 1.25rem;
  font-weight: 700;
  text-align: center;
  margin-left: .25rem;
  margin-right: .25rem;
}
</style>
