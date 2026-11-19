<script setup lang="ts">
import { computed } from 'vue';
import {
  IonItemSliding,
  IonItem,
  IonItemOptions,
  IonItemOption,
  IonIcon,
} from '@ionic/vue';
import {
  notifications,
  notificationsOff,
  trashOutline,
  createOutline,
} from 'ionicons/icons';
import {
  SHORT_WEEK_DAYS,
  isHabitDayActive,
  getFrequencySummary,
  formatHabitRemindersSummary,
} from '@/utils/habitUtils';
import type { Habit } from '@/types';

const props = withDefaults(
  defineProps<{
    habit: Habit;
    cardId?: string;
  }>(),
  {
    cardId: undefined,
  }
);

const emit = defineEmits<{
  (e: 'edit', habitId: number): void;
  (e: 'delete', habit: Habit): void;
}>();

const frequencyLabel = computed(() => getFrequencySummary(props.habit));
const formattedReminder = computed(() => formatHabitRemindersSummary(props.habit.reminders));
</script>

<template>
  <ion-item-sliding class="habit-sliding-item">
    <ion-item-options side="start" class="sliding-options-container">
      <ion-item-option
        class="sliding-option-round"
        aria-label="Editar hábito"
        @click="emit('edit', habit.id)"
      >
        <div class="action-circle-group">
          <div class="action-circle action-circle--edit">
            <ion-icon :icon="createOutline" />
          </div>
          <span class="action-circle-label">Editar</span>
        </div>
      </ion-item-option>
    </ion-item-options>

    <ion-item class="habit-card-item ion-no-padding" lines="none">
      <div
        :id="cardId"
        class="habit-card"
      >
        <div class="habit-card__header">
          <div class="habit-card__info">
            <h3 class="habit-card__title">
              {{ habit.title }}
            </h3>
            <span class="habit-card__subtitle">
              {{ frequencyLabel }}
            </span>
          </div>

          <div class="habit-card__actions" @click.stop>
            <div
              class="reminder-badge"
              :class="{ 'reminder-badge--off': !formattedReminder }"
            >
              <ion-icon
                :icon="formattedReminder ? notifications : notificationsOff"
                class="reminder-badge__icon"
              />
              <span v-if="formattedReminder">
                {{ formattedReminder }}
              </span>
            </div>
          </div>
        </div>

        <div class="week-badges-stack">
          <span
            v-for="(dayLetter, idx) in SHORT_WEEK_DAYS"
            :key="idx"
            class="week-badge-circle"
            :class="{ 'week-badge-circle--active': isHabitDayActive(habit, idx) }"
          >
            {{ dayLetter }}
          </span>
        </div>
      </div>
    </ion-item>

    <ion-item-options side="end" class="sliding-options-container">
      <ion-item-option
        class="sliding-option-round"
        aria-label="Excluir hábito"
        @click="emit('delete', habit)"
      >
        <div class="action-circle-group">
          <div class="action-circle action-circle--delete">
            <ion-icon :icon="trashOutline" />
          </div>
          <span class="action-circle-label">Excluir</span>
        </div>
      </ion-item-option>
    </ion-item-options>
  </ion-item-sliding>
</template>

<style scoped>
.habit-sliding-item {
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: transparent;
}

.habit-card-item {
  --background: transparent;
  --inner-padding-end: 0;
  --padding-start: 0;
  width: 100%;
  position: relative;
  z-index: 2;
}

.sliding-options-container {
  background: transparent;
  border: none;
}

.sliding-option-round {
  --background: transparent;
  --background-hover: transparent;
  --background-activated: transparent;
  --background-focused: transparent;
  --color: inherit;
  --padding-start: 12px;
  --padding-end: 12px;
  --padding-top: 0;
  --padding-bottom: 0;
  background: transparent;
  min-width: 76px;
  height: 100%;
  text-transform: none;
}

.action-circle-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  user-select: none;
  height: 100%;
  text-transform: none;
}

.action-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  transition: transform 0.15s ease, filter 0.15s ease;
}

.action-circle ion-icon {
  font-size: 1.35rem;
}

.sliding-option-round:active .action-circle {
  transform: scale(0.92);
  filter: brightness(0.9);
}

.action-circle--edit {
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-strong);
  color: var(--color-text-primary);
}

.action-circle--delete {
  background: var(--color-danger);
  color: #ffffff;
}

.action-circle-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  text-align: center;
  line-height: 1;
  text-transform: none;
}

.habit-card {
  width: 100%;
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-xl);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.habit-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.habit-card__info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.habit-card__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1.3;
}

.habit-card__subtitle {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.habit-card__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.week-badges-stack {
  display: flex;
  align-items: center;
  padding-left: 2px;
}

.week-badge-circle {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  background: var(--color-background-elevated);
  border: 1.5px solid var(--color-border-strong);
  box-shadow: 0 0 0 2px var(--color-background-secondary);
  color: var(--color-text-secondary);
  margin-left: -6px;
  position: relative;
  transition: transform 0.15s ease, background-color 0.15s ease;
}

.week-badge-circle:first-child {
  margin-left: 0;
}

.week-badge-circle--active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-background-secondary);
  color: var(--color-neutral-900);
  font-weight: 800;
}

.reminder-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-default);
  color: var(--color-text-accent);
  height: 32px;
  padding: 0 10px;
  border-radius: var(--radius-md);
  font-size: 0.8125rem;
  font-weight: 700;
  line-height: 1;
}

.reminder-badge--off {
  width: 32px;
  padding: 0;
  color: var(--color-text-secondary);
}

.reminder-badge__icon {
  font-size: 1.05rem;
  flex-shrink: 0;
}
</style>
