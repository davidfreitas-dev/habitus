<script setup lang="ts">
import { ref } from 'vue';
import {
  FULL_WEEK_DAYS,
  RECURRENCE_PRESETS,
  SHORT_WEEK_DAYS,
} from '@/utils/habitUtils';
import { centerElementInScrollContainer } from '@/utils/domUtils';
import BaseChip from '@/components/base/BaseChip.vue';

const props = defineProps<{
  modelValue: number[];
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', days: number[]): void;
  (e: 'day-removed', dayIndex: number, previousDays: number[]): void;
}>();

const presetsScrollRef = ref<HTMLElement | null>(null);

const isDayChecked = (index: number): boolean => props.modelValue.includes(index);

const isPresetActive = (presetDays: readonly number[]): boolean => {
  if (props.modelValue.length !== presetDays.length) return false;
  return presetDays.every((day) => props.modelValue.includes(day));
};

const applyPreset = (presetDays: readonly number[], event?: MouseEvent) => {
  centerElementInScrollContainer(
    presetsScrollRef.value,
    (event?.currentTarget as HTMLElement | undefined) ?? null
  );
  if (isPresetActive(presetDays)) {
    emit('update:modelValue', []);
  } else {
    emit('update:modelValue', [...presetDays]);
  }
};

const toggleWeekDay = (index: number) => {
  if (props.modelValue.includes(index)) {
    const previousDays = [...props.modelValue];
    emit(
      'update:modelValue',
      props.modelValue.filter((day) => day !== index)
    );
    emit('day-removed', index, previousDays);
    return;
  }

  emit('update:modelValue', [...props.modelValue, index]);
};
</script>

<template>
  <div id="onboarding-recurrence" class="form-section recurrence-section">
    <p class="field-heading">
      Qual a recorrência?
    </p>

    <div ref="presetsScrollRef" class="recurrence-presets">
      <BaseChip
        v-for="preset in RECURRENCE_PRESETS"
        :key="preset.label"
        :label="preset.label"
        :outline="!isPresetActive(preset.days)"
        :is-active="isPresetActive(preset.days)"
        class="preset-chip"
        @click="applyPreset(preset.days, $event)"
      />
    </div>

    <div
      class="week-days-row"
      role="group"
      aria-label="Dias da semana"
    >
      <button
        v-for="(letter, index) in SHORT_WEEK_DAYS"
        :key="index"
        type="button"
        class="week-day-circle"
        :class="{ 'week-day-circle--active': isDayChecked(index) }"
        :aria-pressed="isDayChecked(index)"
        :aria-label="FULL_WEEK_DAYS[index]"
        @click="toggleWeekDay(index)"
      >
        {{ letter }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.form-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.field-heading {
  color: var(--color-text-primary);
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
}

.recurrence-presets {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 2px;
  margin-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.recurrence-presets::-webkit-scrollbar {
  display: none;
}

.week-days-row {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
}

.week-day-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-width: 40px;
  min-height: 44px;
  aspect-ratio: 1 / 1;
  max-height: 48px;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border-default);
  background: var(--color-background-secondary);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}

.week-day-circle--active {
  background: var(--color-primary);
  border-color: var(--color-primary-hover);
  color: var(--color-neutral-900);
}
</style>
