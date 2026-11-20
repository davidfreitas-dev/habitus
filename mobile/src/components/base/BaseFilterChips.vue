<script setup lang="ts" generic="T = string | number | null">
import { ref, watch, onMounted, nextTick } from 'vue';
import { centerElementInScrollContainer } from '@/utils/domUtils';
import BaseChip from '@/components/base/BaseChip.vue';

export interface FilterChipItem<ValueType = string | number | null> {
  label: string;
  value: ValueType;
  disabled?: boolean;
}

const props = withDefaults(
  defineProps<{
    modelValue?: T;
    items?: readonly FilterChipItem<T>[] | FilterChipItem<T>[];
    ariaLabel?: string;
    wrap?: boolean;
  }>(),
  {
    modelValue: undefined,
    items: () => [],
    ariaLabel: 'Filtros',
    wrap: false,
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: T): void;
  (e: 'change', value: T): void;
}>();

const containerRef = ref<HTMLElement | null>(null);

const scrollToIndex = (index: number) => {
  if (!containerRef.value || props.wrap) return;
  const chipEl = containerRef.value.children[index] as HTMLElement | undefined;
  centerElementInScrollContainer(containerRef.value, chipEl ?? null);
};

const scrollByValue = (val: T | undefined) => {
  if (val === undefined || !props.items) return;
  const idx = props.items.findIndex((item) => item.value === val);
  if (idx !== -1) {
    scrollToIndex(idx);
  }
};

const selectChip = (value: T, index: number, disabled?: boolean) => {
  if (disabled) return;
  emit('update:modelValue', value);
  emit('change', value);
  scrollToIndex(index);
};

watch(
  () => props.modelValue,
  (newVal) => {
    nextTick(() => {
      scrollByValue(newVal);
    });
  }
);

onMounted(() => {
  nextTick(() => {
    scrollByValue(props.modelValue);
  });
});
</script>

<template>
  <div
    ref="containerRef"
    class="filter-chips"
    :class="{ 'filter-chips--wrap': wrap }"
    role="tablist"
    :aria-label="ariaLabel"
  >
    <BaseChip
      v-for="(item, index) in items"
      :key="String(item.value)"
      :label="item.label"
      :is-active="modelValue === item.value"
      :disabled="item.disabled"
      role="tab"
      :aria-selected="modelValue === item.value"
      @click="selectChip(item.value, index, item.disabled)"
    >
      <template v-if="$slots.item" #default>
        <slot
          name="item"
          :item="item"
          :active="modelValue === item.value"
        />
      </template>
    </BaseChip>
  </div>
</template>

<style scoped>
.filter-chips {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}

.filter-chips::-webkit-scrollbar {
  display: none;
}

.filter-chips > * {
  flex-shrink: 0;
}

.filter-chips--wrap {
  flex-wrap: wrap;
  overflow-x: visible;
}
</style>
