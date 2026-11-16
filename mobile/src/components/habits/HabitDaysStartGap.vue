<script setup lang="ts">
import { ref } from 'vue';
import dayjs from '@/lib/dayjs';
import { useDayDimensions } from '@/composables/useDayDimensions';

const { daySize } = useDayDimensions();

const startWeekdayFromYear = ref(dayjs().startOf('year').day());
</script>

<template>
  <template
    v-for="(_n, index) in startWeekdayFromYear"
    :key="index"
  >
    <div :style="[ daySize ]" />
  </template>
</template>

<style scoped>
div {
  margin: .25rem;
}
</style>
