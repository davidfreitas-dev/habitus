<script setup lang="ts">
import { computed } from 'vue';
import { IonIcon } from '@ionic/vue';
import { chevronForwardOutline } from 'ionicons/icons';
import { SHORT_DAY_NAMES } from '@/utils/habitUtils';
import ReminderTimePills from '@/components/features/habits/ReminderTimePills.vue';

const props = defineProps<{
  day: number;
  times: string[];
  isExpanded: boolean;
  canCopy: boolean;
}>();

const emit = defineEmits<{
  (e: 'toggle', day: number): void;
  (e: 'edit-time', day: number, time: string): void;
  (e: 'remove-time', day: number, time: string): void;
  (e: 'add-time', day: number): void;
  (e: 'copy', day: number): void;
}>();

const dayName = computed(() => SHORT_DAY_NAMES[props.day]);
const summaryText = computed(() => (props.times.length === 0 ? 'Sem lembrete' : props.times.join(' · ')));
</script>

<template>
  <div class="day-accordion-item">
    <button
      type="button"
      class="day-accordion-trigger"
      :aria-expanded="isExpanded"
      @click="emit('toggle', day)"
    >
      <span class="day-accordion-trigger__name">{{ dayName }}</span>
      <span
        class="day-accordion-trigger__summary"
        :class="{ 'day-accordion-trigger__summary--empty': times.length === 0 }"
      >
        {{ summaryText }}
      </span>
      <ion-icon
        :icon="chevronForwardOutline"
        class="day-accordion-trigger__chevron"
        :class="{ 'day-accordion-trigger__chevron--open': isExpanded }"
      />
    </button>

    <div
      class="day-accordion-collapse"
      :class="{ 'day-accordion-collapse--open': isExpanded }"
      :aria-hidden="!isExpanded"
    >
      <div class="day-accordion-collapse__inner">
        <div class="day-accordion-panel">
          <ReminderTimePills
            :times="times"
            :day-label="dayName"
            :interactive="isExpanded"
            :show-copy="canCopy && times.length > 0"
            :show-limit-inline="true"
            @edit="emit('edit-time', day, $event)"
            @remove="emit('remove-time', day, $event)"
            @add="emit('add-time', day)"
            @copy="emit('copy', day)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.day-accordion-item {
  border-bottom: 1px solid var(--color-border-default);
}

.day-accordion-trigger {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 48px;
  padding: 12px 4px;
  border: none;
  background: transparent;
  color: var(--color-text-primary);
  text-align: left;
  cursor: pointer;
}

.day-accordion-trigger__name {
  min-width: 36px;
  font-size: 0.9375rem;
  font-weight: 700;
}

.day-accordion-trigger__summary {
  flex: 1;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.day-accordion-trigger__summary--empty {
  font-style: italic;
}

.day-accordion-trigger__chevron {
  font-size: 1rem;
  color: var(--color-text-secondary);
  transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.day-accordion-trigger__chevron--open {
  transform: rotate(90deg);
}

.day-accordion-collapse {
  display: grid;
  grid-template-rows: 0fr;
  opacity: 0;
  transition:
    grid-template-rows 0.24s cubic-bezier(0.22, 1, 0.36, 1),
    opacity 0.2s ease;
}

.day-accordion-collapse--open {
  grid-template-rows: 1fr;
  opacity: 1;
}

.day-accordion-collapse__inner {
  overflow: hidden;
  min-height: 0;
}

.day-accordion-panel {
  padding: 4px 4px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>
