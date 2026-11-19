<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { IonIcon, IonLabel } from '@ionic/vue';
import { chevronForwardOutline, notificationsOutline } from 'ionicons/icons';
import { useNotifications } from '@/composables/useNotifications';
import { useHaptics } from '@/composables/useHaptics';
import { useToast } from '@/composables/useToast';
import {
  FULL_WEEK_DAYS,
  MAX_REMINDERS_PER_DAY,
  RECURRENCE_PRESETS,
  SHORT_WEEK_DAYS,
  parseWeekDays,
} from '@/utils/habitUtils';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseToggle from '@/components/base/BaseToggle.vue';
import BaseChip from '@/components/base/BaseChip.vue';
import ReminderSheet, {
  type ReminderScheduleState,
} from '@/components/features/habits/ReminderSheet.vue';
import type { HabitDraft, HabitFormSubmitPayload, HabitReminder } from '@/types';

const emit = defineEmits<{
  (e: 'onError', message: string): void;
  (e: 'onSubmit', payload: HabitFormSubmitPayload): void;
}>();

const props = withDefaults(
  defineProps<{
    habit?: HabitDraft;
    isLoading?: boolean;
  }>(),
  {
    habit: () => ({
      id: null,
      title: '',
      week_days: '',
      reminders: [],
    }),
    isLoading: false,
  }
);

const { requestPermission } = useNotifications();
const { triggerHaptic } = useHaptics();
const { showToast, showUndoToast } = useToast();

const title = ref('');
const weekDays = ref<number[]>([]);
const isReminderSheetOpen = ref(false);
const isHydrating = ref(false);

const reminderState = ref<ReminderScheduleState>({
  enabled: false,
  mode: 'all',
  times: [],
  perDay: {},
});

const sortedActiveWeekDays = computed(() => [...weekDays.value].sort((a, b) => a - b));

const hydrateRemindersFromHabit = (reminders: HabitReminder[] | undefined, days: number[]) => {
  if (!reminders || reminders.length === 0) {
    reminderState.value = {
      enabled: false,
      mode: 'all',
      times: [],
      perDay: {},
    };
    return;
  }

  const grouped: Record<number, string[]> = {};
  for (const r of reminders) {
    const day = Number(r.week_day);
    const time = r.time.substring(0, 5);
    if (!grouped[day]) grouped[day] = [];
    if (!grouped[day].includes(time)) {
      grouped[day].push(time);
    }
  }

  for (const day of Object.keys(grouped)) {
    grouped[Number(day)].sort();
  }

  const activeDays = days.length > 0 ? days : Object.keys(grouped).map(Number);
  const firstSignature = activeDays.length > 0 ? (grouped[activeDays[0]] ?? []).join(',') : '';
  const allSame =
    firstSignature.length > 0 &&
    activeDays.every((d) => (grouped[d] ?? []).join(',') === firstSignature);

  if (allSame) {
    reminderState.value = {
      enabled: true,
      mode: 'all',
      times: [...(grouped[activeDays[0]] ?? [])].slice(0, MAX_REMINDERS_PER_DAY),
      perDay: {},
    };
  } else {
    reminderState.value = {
      enabled: true,
      mode: 'perDay',
      times: [],
      perDay: grouped,
    };
  }
};

watch(
  () => props.habit,
  (newHabit) => {
    if (newHabit) {
      isHydrating.value = true;
      title.value = newHabit.title || '';
      const parsedDays = parseWeekDays(newHabit.week_days);
      weekDays.value = parsedDays;
      hydrateRemindersFromHabit(newHabit.reminders, parsedDays);
      isHydrating.value = false;
    }
  },
  { immediate: true }
);

const handleReminderToggle = async (enabled: boolean) => {
  void triggerHaptic('light');

  if (!enabled) {
    reminderState.value = { ...reminderState.value, enabled: false };
    return;
  }

  const granted = await requestPermission();
  if (!granted) {
    reminderState.value = { ...reminderState.value, enabled: false };
    void showToast(
      'error',
      'Permissão de notificações negada. Ative em Ajustes > Aplicativos > Habitus > Notificações.'
    );
    return;
  }

  reminderState.value = { ...reminderState.value, enabled: true };
};

const clearFormData = () => {
  title.value = '';
  weekDays.value = [];
  reminderState.value = {
    enabled: false,
    mode: 'all',
    times: [],
    perDay: {},
  };
};

defineExpose({ clearFormData });

const isDayChecked = (index: number): boolean => weekDays.value.includes(index);

const toggleWeekDay = (index: number) => {
  if (weekDays.value.includes(index)) {
    const previousDays = [...weekDays.value];
    const removedCustomTimes = reminderState.value.perDay[index]
      ? [...reminderState.value.perDay[index]]
      : [];

    weekDays.value = weekDays.value.filter((day) => day !== index);

    if (reminderState.value.mode === 'perDay' && removedCustomTimes.length > 0) {
      const nextPerDay = { ...reminderState.value.perDay };
      delete nextPerDay[index];
      reminderState.value = { ...reminderState.value, perDay: nextPerDay };

      void showUndoToast(
        `${FULL_WEEK_DAYS[index]} e seus lembretes removidos`,
        () => {
          weekDays.value = previousDays;
          reminderState.value = {
            ...reminderState.value,
            perDay: {
              ...reminderState.value.perDay,
              [index]: removedCustomTimes,
            },
          };
        }
      );
    }
    return;
  }

  weekDays.value = [...weekDays.value, index];
};

const presetsScrollRef = ref<HTMLElement | null>(null);
const templatesScrollRef = ref<HTMLElement | null>(null);

const centerChip = (container: HTMLElement | null, event?: MouseEvent) => {
  if (!container || !event?.currentTarget) return;
  const chipEl = event.currentTarget as HTMLElement;
  const targetScrollLeft = chipEl.offsetLeft - container.offsetWidth / 2 + chipEl.offsetWidth / 2;

  if (typeof container.scrollTo === 'function') {
    container.scrollTo({
      left: Math.max(0, targetScrollLeft),
      behavior: 'smooth',
    });
  } else {
    container.scrollLeft = Math.max(0, targetScrollLeft);
  }
};

const isPresetActive = (presetDays: readonly number[]): boolean => {
  if (weekDays.value.length !== presetDays.length) return false;
  return presetDays.every((day) => weekDays.value.includes(day));
};

const applyPreset = (presetDays: readonly number[], event?: MouseEvent) => {
  centerChip(presetsScrollRef.value, event);
  if (isPresetActive(presetDays)) {
    weekDays.value = [];
  } else {
    weekDays.value = [...presetDays];
  }
};

const reminderSummaryText = computed(() => {
  if (!reminderState.value.enabled) return 'Desativado';

  if (reminderState.value.mode === 'all') {
    const times = reminderState.value.times;
    if (times.length === 0) return 'Sem horários definidos';
    const label = times.length === 1 ? '1 horário' : `${times.length} horários`;
    return `${label} · ${times.join(' · ')}`;
  }

  const totalPerDay = sortedActiveWeekDays.value.reduce(
    (sum, day) => sum + (reminderState.value.perDay[day]?.length ?? 0),
    0
  );
  if (totalPerDay === 0) return 'Sem horários definidos';
  const label = totalPerDay === 1 ? '1 lembrete' : `${totalPerDay} lembretes`;
  return `Personalizado · ${label}/sem`;
});

const openReminderSheet = () => {
  if (!reminderState.value.enabled) return;
  if (weekDays.value.length === 0) {
    emit('onError', 'Selecione ao menos um dia da semana antes de configurar lembretes.');
    return;
  }
  isReminderSheetOpen.value = true;
};

const buildRemindersPayload = (): HabitReminder[] => {
  if (!reminderState.value.enabled) return [];

  const reminders: HabitReminder[] = [];
  if (reminderState.value.mode === 'all') {
    for (const day of sortedActiveWeekDays.value) {
      for (const time of reminderState.value.times) {
        reminders.push({ week_day: day, time });
      }
    }
  } else {
    for (const day of sortedActiveWeekDays.value) {
      for (const time of reminderState.value.perDay[day] ?? []) {
        reminders.push({ week_day: day, time });
      }
    }
  }

  return reminders;
};

const submitForm = () => {
  if (!title.value || !weekDays.value.length) {
    emit('onError', 'Informe um título para o hábito e selecione os dias.');
    return;
  }

  const reminders = buildRemindersPayload();
  if (reminderState.value.enabled && reminders.length === 0) {
    emit('onError', 'Adicione ao menos um horário de lembrete ou desative os lembretes.');
    return;
  }

  emit('onSubmit', {
    title: title.value,
    weekDays: weekDays.value,
    reminderEnabled: reminderState.value.enabled,
    reminders,
  });
};

interface HabitTemplate {
  title: string;
  emoji: string;
  weekDays: number[];
  reminderTimes: string[];
}

const habitTemplates: HabitTemplate[] = [
  {
    emoji: '💧',
    title: 'Beber 2L de água',
    weekDays: [0, 1, 2, 3, 4, 5, 6],
    reminderTimes: ['08:00', '12:00', '16:00', '20:00'],
  },
  { emoji: '🏃', title: 'Caminhada 30 min', weekDays: [1, 2, 3, 4, 5], reminderTimes: ['07:00'] },
  { emoji: '📖', title: 'Ler 15 páginas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['21:00'] },
  { emoji: '🧘', title: 'Meditar 10 min', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['08:00'] },
  { emoji: '😴', title: 'Dormir 8 horas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTimes: ['22:30'] },
  { emoji: '💻', title: 'Estudar 45 min', weekDays: [1, 2, 3, 4, 5], reminderTimes: ['19:00'] },
];

const applyTemplate = (template: HabitTemplate, event?: MouseEvent) => {
  centerChip(templatesScrollRef.value, event);
  title.value = template.title;
  weekDays.value = [...template.weekDays];
  if (template.reminderTimes.length > 0) {
    reminderState.value = {
      enabled: true,
      mode: 'all',
      times: [...template.reminderTimes],
      perDay: {},
    };
  }
};
</script>

<template>
  <form class="habit-form" @submit.prevent="submitForm">
    <div v-if="!habit?.id" class="form-section templates-section">
      <p class="section-label">
        Sugestões populares
      </p>
      <div ref="templatesScrollRef" class="templates-scroll">
        <BaseChip
          v-for="tpl in habitTemplates"
          :key="tpl.title"
          :outline="true"
          class="template-chip"
          @click="applyTemplate(tpl, $event)"
        >
          <span class="template-emoji">{{ tpl.emoji }}</span>
          <ion-label>{{ tpl.title }}</ion-label>
        </BaseChip>
      </div>
    </div>

    <div class="form-section">
      <p class="field-heading">
        Qual seu comprometimento?
      </p>
      <BaseInput
        id="onboarding-habit-name"
        v-model="title"
        placeholder="Exercícios, dormir bem, etc..."
        @keyup.enter="submitForm"
      />
    </div>

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

    <div id="onboarding-reminder" class="reminder-card">
      <div class="reminder-card__top">
        <div class="reminder-card__title-group">
          <span
            class="reminder-card__icon-box"
            aria-hidden="true"
          >
            <ion-icon :icon="notificationsOutline" />
          </span>
          <span class="reminder-card__title">Lembretes</span>
        </div>
        <BaseToggle
          :checked="reminderState.enabled"
          aria-label="Ativar lembretes"
          @update:checked="handleReminderToggle"
        />
      </div>

      <div class="reminder-card__divider" />

      <button
        type="button"
        class="reminder-card__summary-btn"
        :class="{ 'reminder-card__summary-btn--disabled': !reminderState.enabled }"
        :disabled="!reminderState.enabled"
        aria-label="Configurar horários de lembrete"
        @click="openReminderSheet"
      >
        <span class="reminder-card__summary-text">{{ reminderSummaryText }}</span>
        <ion-icon
          v-if="reminderState.enabled"
          :icon="chevronForwardOutline"
          class="reminder-card__chevron"
        />
      </button>
    </div>

    <div class="form-actions">
      <BaseButton
        type="submit"
        color="primary"
        :is-loading="isLoading"
      >
        Confirmar
      </BaseButton>
    </div>

    <ReminderSheet
      v-model:is-open="isReminderSheetOpen"
      v-model="reminderState"
      :active-week-days="weekDays"
    />
  </form>
</template>

<style scoped>
.habit-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-top: 0;
}

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

.templates-section .section-label {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--color-text-secondary);
}

.templates-scroll,
.recurrence-presets {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 2px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.recurrence-presets {
  margin-bottom: 4px;
}

.templates-scroll::-webkit-scrollbar,
.recurrence-presets::-webkit-scrollbar {
  display: none;
}

.template-emoji {
  margin-right: 6px;
  font-size: 0.95rem;
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

.reminder-card {
  border-radius: var(--radius-xl);
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border-default);
  overflow: hidden;
}

.reminder-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
}

.reminder-card__title-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reminder-card__icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: var(--radius-full);
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-default);
  color: var(--color-text-accent);
  font-size: 1.25rem;
}

.form-actions {
  padding-top: 4px;
}

.reminder-card__title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.reminder-card__divider {
  height: 1px;
  background: var(--color-border-default);
}

.reminder-card__summary-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  min-height: 48px;
  padding: 12px 16px;
  border: none;
  background: transparent;
  color: var(--color-text-primary);
  text-align: left;
  cursor: pointer;
}

.reminder-card__summary-btn--disabled {
  color: var(--color-text-secondary);
  cursor: default;
}

.reminder-card__summary-text {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.reminder-card__chevron {
  font-size: 1rem;
  color: var(--color-text-secondary);
  flex-shrink: 0;
}
</style>
