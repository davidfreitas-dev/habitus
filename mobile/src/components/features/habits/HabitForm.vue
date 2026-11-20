<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { IonLabel } from '@ionic/vue';
import { useNotifications } from '@/composables/useNotifications';
import { useHaptics } from '@/composables/useHaptics';
import { useToast } from '@/composables/useToast';
import {
  FULL_WEEK_DAYS,
  HABIT_TEMPLATES,
  buildRemindersPayload,
  createDefaultReminderState,
  hydrateRemindersFromHabit,
  parseWeekDays,
} from '@/utils/habitUtils';
import { centerElementInScrollContainer } from '@/utils/domUtils';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseChip from '@/components/base/BaseChip.vue';
import HabitWeekDayPicker from '@/components/features/habits/HabitWeekDayPicker.vue';
import HabitReminderCard from '@/components/features/habits/HabitReminderCard.vue';
import ReminderSheet from '@/components/features/habits/ReminderSheet.vue';
import type {
  HabitDraft,
  HabitFormSubmitPayload,
  HabitTemplate,
  ReminderScheduleState,
} from '@/types';

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
const templatesScrollRef = ref<HTMLElement | null>(null);
const reminderState = ref<ReminderScheduleState>(createDefaultReminderState());

const sortedActiveWeekDays = computed(() => [...weekDays.value].sort((a, b) => a - b));

watch(
  () => props.habit,
  (newHabit) => {
    if (!newHabit) return;
    title.value = newHabit.title || '';
    const parsedDays = parseWeekDays(newHabit.week_days);
    weekDays.value = parsedDays;
    reminderState.value = hydrateRemindersFromHabit(newHabit.reminders, parsedDays);
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
  reminderState.value = createDefaultReminderState();
};

defineExpose({ clearFormData });

const handleWeekDayRemoved = (index: number, previousDays: number[]) => {
  const removedCustomTimes = reminderState.value.perDay[index]
    ? [...reminderState.value.perDay[index]]
    : [];

  if (reminderState.value.mode !== 'perDay' || removedCustomTimes.length === 0) return;

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

const submitForm = () => {
  if (!title.value || !weekDays.value.length) {
    emit('onError', 'Informe um título para o hábito e selecione os dias.');
    return;
  }

  const reminders = buildRemindersPayload(reminderState.value, sortedActiveWeekDays.value);
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

const applyTemplate = (template: HabitTemplate, event?: MouseEvent) => {
  centerElementInScrollContainer(
    templatesScrollRef.value,
    (event?.currentTarget as HTMLElement | undefined) ?? null
  );
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
          v-for="tpl in HABIT_TEMPLATES"
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

    <HabitWeekDayPicker
      v-model="weekDays"
      @day-removed="handleWeekDayRemoved"
    />

    <HabitReminderCard
      :enabled="reminderState.enabled"
      :summary-text="reminderSummaryText"
      @toggle="handleReminderToggle"
      @open-sheet="openReminderSheet"
    />

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

.templates-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 2px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.templates-scroll::-webkit-scrollbar {
  display: none;
}

.template-emoji {
  margin-right: 6px;
  font-size: 0.95rem;
}

.form-actions {
  padding-top: 4px;
}
</style>
