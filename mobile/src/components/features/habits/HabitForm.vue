<script setup lang="ts">
import { ref, watch } from 'vue';
import { IonDatetime, IonDatetimeButton, IonModal, IonItem, IonLabel } from '@ionic/vue';
import { useNotifications } from '@/composables/useNotifications';
import { useHaptics } from '@/composables/useHaptics';
import {
  FULL_WEEK_DAYS,
  RECURRENCE_PRESETS,
  parseWeekDays,
  formatReminderTime,
  getLocalISOString,
  timeStringToLocalISO,
} from '@/utils/habitUtils';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseCheckbox from '@/components/base/BaseCheckbox.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseToggle from '@/components/base/BaseToggle.vue';
import BaseChip from '@/components/base/BaseChip.vue';
import type { HabitDraft, HabitFormSubmitPayload } from '@/types';

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
      reminder_time: null,
    }),
    isLoading: false,
  }
);

const { requestPermission } = useNotifications();
const { triggerHaptic } = useHaptics();

const formData = ref<{
  title: string;
  weekDays: number[];
  reminderEnabled: boolean;
  reminderTime: string;
}>({
  title: '',
  weekDays: [],
  reminderEnabled: false,
  reminderTime: getLocalISOString(),
});

watch(
  () => props.habit,
  (newHabit) => {
    if (newHabit) {
      formData.value.title = newHabit.title || '';
      formData.value.weekDays = parseWeekDays(newHabit.week_days);

      if (newHabit.reminder_time) {
        formData.value.reminderEnabled = true;
        formData.value.reminderTime = newHabit.reminder_time;
      } else {
        formData.value.reminderEnabled = false;
        formData.value.reminderTime = getLocalISOString();
      }
    }
  },
  { immediate: true }
);

watch(
  () => formData.value.reminderEnabled,
  async (enabled) => {
    if (!enabled) return;
    const granted = await requestPermission();
    if (!granted) {
      formData.value.reminderEnabled = false;
      emit('onError', 'Permissão de notificações negada nas configurações do aparelho.');
    }
  }
);

const clearFormData = () => {
  formData.value.title = '';
  formData.value.weekDays = [];
  formData.value.reminderEnabled = false;
  formData.value.reminderTime = getLocalISOString();
};

defineExpose({ clearFormData });

const isDayChecked = (index: number) => {
  return formData.value.weekDays.includes(index);
};

const toggleWeekDay = async (index: number) => {
  await triggerHaptic('light');
  const days = formData.value.weekDays;

  if (days.includes(index)) {
    formData.value.weekDays = days.filter((day) => day !== index);
    return;
  }

  days.push(index);
};

const templatesScrollRef = ref<HTMLElement | null>(null);
const presetsScrollRef = ref<HTMLElement | null>(null);

const centerChip = (container: HTMLElement | null, event?: MouseEvent) => {
  if (!container || !event?.currentTarget) return;
  const chipEl = event.currentTarget as HTMLElement;

  const chipLeft = chipEl.offsetLeft;
  const chipWidth = chipEl.offsetWidth;
  const containerWidth = container.offsetWidth;
  const targetScrollLeft = chipLeft - (containerWidth / 2) + (chipWidth / 2);

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
  if (formData.value.weekDays.length !== presetDays.length) return false;
  return presetDays.every((day) => formData.value.weekDays.includes(day));
};

const applyPreset = async (presetDays: readonly number[], event?: MouseEvent) => {
  await triggerHaptic('light');
  centerChip(presetsScrollRef.value, event);
  if (isPresetActive(presetDays)) {
    formData.value.weekDays = [];
  } else {
    formData.value.weekDays = [...presetDays];
  }
};

const submitForm = () => {
  if (!formData.value.title || !formData.value.weekDays.length) {
    emit('onError', 'Informe um título para o hábito e selecione os dias.');
    return;
  }

  const reminderTimeValue = formatReminderTime(formData.value.reminderTime);

  emit('onSubmit', {
    ...formData.value,
    reminder_time: formData.value.reminderEnabled ? reminderTimeValue : null,
  });
};

interface HabitTemplate {
  title: string;
  emoji: string;
  weekDays: number[];
  reminderTime: string | null;
}

const habitTemplates: HabitTemplate[] = [
  { emoji: '💧', title: 'Beber 2L de água', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTime: '09:00' },
  { emoji: '🏃', title: 'Caminhada 30 min', weekDays: [1, 2, 3, 4, 5], reminderTime: '07:00' },
  { emoji: '📖', title: 'Ler 15 páginas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTime: '21:00' },
  { emoji: '🧘', title: 'Meditar 10 min', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTime: '08:00' },
  { emoji: '😴', title: 'Dormir 8 horas', weekDays: [0, 1, 2, 3, 4, 5, 6], reminderTime: '22:30' },
  { emoji: '💻', title: 'Estudar 45 min', weekDays: [1, 2, 3, 4, 5], reminderTime: '19:00' },
];

const applyTemplate = async (template: HabitTemplate, event?: MouseEvent) => {
  await triggerHaptic('light');
  centerChip(templatesScrollRef.value, event);
  formData.value.title = template.title;
  formData.value.weekDays = [...template.weekDays];
  if (template.reminderTime) {
    formData.value.reminderEnabled = true;
    formData.value.reminderTime = timeStringToLocalISO(template.reminderTime);
  }
};
</script>

<template>
  <form @submit.prevent="submitForm">
    <div v-if="!habit?.id" class="templates-section">
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

    <p>Qual seu comprometimento?</p>
    <BaseInput
      id="onboarding-habit-name"
      v-model="formData.title"
      placeholder="Exercícios, dormir bem, etc..."
      @keyup.enter="submitForm"
    />

    <div id="onboarding-recurrence">
      <p>Qual a recorrência?</p>

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

      <BaseCheckbox
        v-for="(weekDay, index) in FULL_WEEK_DAYS"
        :key="index"
        :label="weekDay"
        :is-checked="isDayChecked(index)"
        @handle-checkbox-change="toggleWeekDay(index)"
      />
    </div>

    <div id="onboarding-reminder">
      <ion-item lines="none" class="ion-no-padding">
        <ion-label class="ion-no-margin">
          <b>Ativar lembrete</b>
        </ion-label>
        <BaseToggle v-model:checked="formData.reminderEnabled" />
      </ion-item>
      <ion-item
        v-if="formData.reminderEnabled"
        lines="none"
        class="ion-no-padding"
      >
        <ion-label class="ion-no-margin">
          Horário
        </ion-label>
        <ion-datetime-button datetime="reminder-datetime" />
        <ion-modal :keep-contents-mounted="true">
          <ion-datetime
            id="reminder-datetime"
            v-model="formData.reminderTime"
            presentation="time"
            locale="pt-BR"
          />
        </ion-modal>
      </ion-item>
    </div>

    <BaseButton
      type="submit"
      color="primary"
      class="ion-margin-top"
      :is-loading="isLoading"
    >
      Confirmar
    </BaseButton>
  </form>
</template>

<style scoped>
p {
  color: var(--color-text-primary);
  font-weight: 700;
  margin-top: 1.5rem;
}

.templates-section {
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}

.templates-section .section-label {
  margin-top: 0;
  margin-bottom: 8px;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.templates-scroll,
.recurrence-presets {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 6px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.recurrence-presets {
  margin-bottom: 12px;
}

.templates-scroll::-webkit-scrollbar,
.recurrence-presets::-webkit-scrollbar {
  display: none;
}

.template-emoji {
  margin-right: 6px;
  font-size: 0.95rem;
}

ion-item {
  color: var(--color-text-accent);
  font-size: 1.1rem;
  --inner-padding-end: 0;
}

ion-datetime-button::part(native) {
  background: var(--color-background-secondary);
  color: var(--color-text-accent);
  border-radius: 8px;
  font-size: 1rem;
  padding: 6px 12px;
}

ion-modal {
  --background: var(--color-background-elevated);
  --backdrop-opacity: 0.7;
  --border-radius: 16px;
}

ion-modal ion-datetime {
  --background: var(--color-background-elevated);
  --background-rgb: var(--color-background-rgb);
  --wheel-highlight-background: var(--color-background-elevated);
}

ion-modal ion-datetime::part(wheel-item) {
  color: var(--color-text-primary);
}

ion-modal ion-datetime::part(wheel-item active) {
  color: var(--color-success);
}
</style>
