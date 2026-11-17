<script setup lang="ts">
import { ref, watch } from 'vue';
import { IonDatetime, IonDatetimeButton, IonModal, IonItem, IonLabel, IonChip } from '@ionic/vue';
import { useNotifications } from '@/composables/useNotifications';
import { useHaptics } from '@/composables/useHaptics';
import Input from '@/components/ui/Input.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
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

const getLocalISOString = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60000;
  return new Date(now.getTime() - offset).toISOString();
};

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
      formData.value.weekDays = [];
      if (newHabit.week_days) {
        if (Array.isArray(newHabit.week_days)) {
          formData.value.weekDays = newHabit.week_days.map(Number);
        } else if (typeof newHabit.week_days === 'string' && newHabit.week_days.length > 0) {
          formData.value.weekDays = newHabit.week_days.split(',').map(Number);
        }
      }

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

const recurrencePresets = [
  { label: 'Todos os dias', days: [0, 1, 2, 3, 4, 5, 6] },
  { label: 'Dias úteis', days: [1, 2, 3, 4, 5] },
  { label: 'Fim de semana', days: [0, 6] },
] as const;

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

  let reminderTimeValue = formData.value.reminderTime;

  if (reminderTimeValue && reminderTimeValue.includes('T')) {
    reminderTimeValue = reminderTimeValue.split('T')[1].substring(0, 5);
  } else if (reminderTimeValue && reminderTimeValue.length > 5) {
    reminderTimeValue = reminderTimeValue.substring(0, 5);
  }

  emit('onSubmit', {
    ...formData.value,
    reminder_time: formData.value.reminderEnabled ? reminderTimeValue : null,
  });
};

const availableWeekDays = [
  'Domingo',
  'Segunda-feira',
  'Terça-feira',
  'Quarta-feira',
  'Quinta-feira',
  'Sexta-feira',
  'Sábado',
];

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
    const today = new Date();
    const [hh, mm] = template.reminderTime.split(':');
    today.setHours(Number(hh), Number(mm), 0, 0);
    const offset = today.getTimezoneOffset() * 60000;
    formData.value.reminderTime = new Date(today.getTime() - offset).toISOString();
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
        <ion-chip
          v-for="tpl in habitTemplates"
          :key="tpl.title"
          :outline="true"
          class="template-chip"
          @click="applyTemplate(tpl, $event)"
        >
          <span class="template-emoji">{{ tpl.emoji }}</span>
          <ion-label>{{ tpl.title }}</ion-label>
        </ion-chip>
      </div>
    </div>

    <p>Qual seu comprometimento?</p>
    <Input
      id="onboarding-habit-name"
      v-model="formData.title"
      placeholder="Exercícios, dormir bem, etc..."
      @keyup.enter="submitForm"
    />

    <div id="onboarding-recurrence">
      <p>Qual a recorrência?</p>

      <div ref="presetsScrollRef" class="recurrence-presets">
        <ion-chip
          v-for="preset in recurrencePresets"
          :key="preset.label"
          :outline="!isPresetActive(preset.days)"
          class="preset-chip"
          :class="{ active: isPresetActive(preset.days) }"
          @click="applyPreset(preset.days, $event)"
        >
          <ion-label>{{ preset.label }}</ion-label>
        </ion-chip>
      </div>

      <Checkbox
        v-for="(weekDay, index) in availableWeekDays"
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
        <Toggle v-model:checked="formData.reminderEnabled" />
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

    <Button
      type="submit"
      color="primary"
      class="ion-margin-top"
      :is-loading="isLoading"
    >
      Confirmar
    </Button>
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

.templates-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 6px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.templates-scroll::-webkit-scrollbar {
  display: none;
}

.template-chip {
  --background: var(--color-background-secondary);
  --color: var(--color-text-secondary);
  border: 1px solid var(--color-border-default);
  margin: 0;
  flex-shrink: 0;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.template-chip:active {
  --background: var(--color-background-elevated);
}

.template-emoji {
  margin-right: 6px;
  font-size: 0.95rem;
}

.recurrence-presets {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 6px;
  margin-bottom: 12px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.recurrence-presets::-webkit-scrollbar {
  display: none;
}

.preset-chip {
  --background: var(--color-background-secondary);
  --color: var(--color-text-secondary);
  border: 1px solid var(--color-border-default);
  font-size: 0.8125rem;
  font-weight: 700;
  margin: 0;
  flex-shrink: 0;
  cursor: pointer;
  transition: all 0.15s ease;
}

.preset-chip.active {
  --background: var(--color-primary);
  --color: var(--color-neutral-900);
  border-color: var(--color-primary);
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
