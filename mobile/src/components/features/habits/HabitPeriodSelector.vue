<script setup lang="ts">
import BaseSegment, { type SegmentItem } from '@/components/base/BaseSegment.vue';
import type { StatsPeriod } from '@/types';

defineProps<{
  modelValue: StatsPeriod | string;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: StatsPeriod): void;
}>();

const periods: SegmentItem[] = [
  { label: 'S', value: 'W' },
  { label: 'M', value: 'M' },
  { label: '3M', value: '3M' },
  { label: '6M', value: '6M' },
  { label: 'A', value: 'Y' },
];
</script>

<template>
  <BaseSegment
    :model-value="modelValue"
    :segments="periods"
    @update:model-value="emit('update:modelValue', $event as StatsPeriod)"
  />
</template>
