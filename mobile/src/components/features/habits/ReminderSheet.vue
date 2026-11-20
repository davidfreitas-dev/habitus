<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { IonModal } from '@ionic/vue';
import { MAX_REMINDERS_PER_DAY, SHORT_DAY_NAMES } from '@/utils/habitUtils';
import { useHaptics } from '@/composables/useHaptics';
import { useToast } from '@/composables/useToast';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseSegment, { type SegmentItem } from '@/components/base/BaseSegment.vue';
import BaseAlert from '@/components/base/BaseAlert.vue';
import ReminderTimePills from '@/components/features/habits/ReminderTimePills.vue';
import ReminderDayAccordion from '@/components/features/habits/ReminderDayAccordion.vue';
import ReminderTimePickerModal from '@/components/features/habits/ReminderTimePickerModal.vue';
import type { ReminderMode, ReminderScheduleState } from '@/types';

export type { ReminderMode, ReminderScheduleState };

const modeSegments: SegmentItem[] = [
  { label: 'Todos os dias', value: 'all' },
  { label: 'Por dia', value: 'perDay' },
];

const props = defineProps<{
  isOpen: boolean;
  modelValue: ReminderScheduleState;
  activeWeekDays: number[];
}>();

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void;
  (e: 'update:modelValue', value: ReminderScheduleState): void;
}>();

const { triggerHaptic } = useHaptics();
const { showToast, showUndoToast } = useToast();

const sheetHeaderRef = ref<HTMLElement | null>(null);
const previousFocusedElement = ref<HTMLElement | null>(null);
const alertRef = ref<InstanceType<typeof BaseAlert> | null>(null);

const expandedDay = ref<number | null>(null);
const isTimePickerOpen = ref(false);
const pickerTarget = ref<number | 'all'>('all');
const editingOriginalTime = ref<string | null>(null);

const sortedActiveDays = computed(() => [...props.activeWeekDays].sort((a, b) => a - b));

const totalWeeklyCount = computed(() => {
  if (props.modelValue.mode === 'all') {
    return sortedActiveDays.value.length * props.modelValue.times.length;
  }
  return sortedActiveDays.value.reduce(
    (sum, day) => sum + (props.modelValue.perDay[day]?.length ?? 0),
    0
  );
});

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      previousFocusedElement.value = document.activeElement as HTMLElement | null;
      if (
        props.modelValue.mode === 'perDay' &&
        (expandedDay.value === null || !sortedActiveDays.value.includes(expandedDay.value))
      ) {
        expandedDay.value = sortedActiveDays.value[0] ?? null;
      }
    }
  }
);

watch(sortedActiveDays, (days) => {
  if (expandedDay.value !== null && !days.includes(expandedDay.value)) {
    expandedDay.value = days[0] ?? null;
  }
});

const handleSheetDidPresent = async () => {
  await nextTick();
  sheetHeaderRef.value?.focus();
};

const handleSheetDidDismiss = () => {
  emit('update:isOpen', false);
  previousFocusedElement.value?.focus?.();
};

const updateState = (patch: Partial<ReminderScheduleState>) => {
  emit('update:modelValue', {
    ...props.modelValue,
    ...patch,
  });
};

const getTargetTimes = (target: number | 'all'): string[] =>
  target === 'all' ? props.modelValue.times : (props.modelValue.perDay[target] ?? []);

const setTargetTimes = (target: number | 'all', nextTimes: string[]) => {
  if (target === 'all') {
    updateState({ times: nextTimes });
    return;
  }
  updateState({
    perDay: {
      ...props.modelValue.perDay,
      [target]: nextTimes,
    },
  });
};

const hasDivergentPerDay = (): boolean => {
  if (sortedActiveDays.value.length <= 1) return false;
  const signatures = new Set(
    sortedActiveDays.value.map((d) => (props.modelValue.perDay[d] ?? []).join(','))
  );
  return signatures.size > 1;
};

const switchMode = async (nextMode: ReminderMode) => {
  if (nextMode === props.modelValue.mode) return;

  if (nextMode === 'perDay') {
    const nextPerDay: Record<number, string[]> = {};
    for (const day of sortedActiveDays.value) {
      const existing = props.modelValue.perDay[day];
      nextPerDay[day] = existing && existing.length > 0 ? [...existing] : [...props.modelValue.times];
    }
    expandedDay.value = sortedActiveDays.value[0] ?? null;
    updateState({ mode: 'perDay', perDay: nextPerDay });
    return;
  }

  const mergePerDayToAll = () => {
    const merged = new Set<string>();
    for (const day of sortedActiveDays.value) {
      for (const time of props.modelValue.perDay[day] ?? []) {
        merged.add(time);
      }
    }
    updateState({
      mode: 'all',
      times: Array.from(merged).sort().slice(0, MAX_REMINDERS_PER_DAY),
    });
  };

  if (hasDivergentPerDay()) {
    await alertRef.value?.setOpen(
      'Unificar horários?',
      'Você tem horários diferentes por dia. Ao voltar para "Mesmo horário todo dia", eles serão unificados em uma lista única.',
      {
        buttons: [
          { text: 'Cancelar', role: 'cancel' },
          { text: 'Unificar', handler: mergePerDayToAll },
        ],
      }
    );
    return;
  }

  mergePerDayToAll();
};

const openTimePicker = (target: number | 'all', existingTime: string | null = null) => {
  const list = getTargetTimes(target);
  if (!existingTime && list.length >= MAX_REMINDERS_PER_DAY) {
    void showToast('info', `Limite máximo de ${MAX_REMINDERS_PER_DAY} horários por dia.`);
    return;
  }

  pickerTarget.value = target;
  editingOriginalTime.value = existingTime;
  isTimePickerOpen.value = true;
};

const confirmTimePicker = (formatted: string) => {
  const target = pickerTarget.value;
  const current = getTargetTimes(target);
  const original = editingOriginalTime.value;
  const base = original ? current.filter((t) => t !== original) : current;

  if (base.includes(formatted)) {
    const suffix = target === 'all' ? '' : ' neste dia';
    void showToast('info', `Este horário já foi adicionado${suffix}.`);
    return;
  }
  if (base.length >= MAX_REMINDERS_PER_DAY) {
    void showToast('info', `Limite máximo de ${MAX_REMINDERS_PER_DAY} horários por dia.`);
    return;
  }

  setTargetTimes(target, [...base, formatted].sort());
  void triggerHaptic('light');
  editingOriginalTime.value = null;
  isTimePickerOpen.value = false;
};

const removeTime = (target: number | 'all', time: string) => {
  void triggerHaptic('light');
  const previous = [...getTargetTimes(target)];
  setTargetTimes(
    target,
    previous.filter((t) => t !== time)
  );

  const label =
    target === 'all'
      ? `Horário ${time} removido`
      : `Horário ${time} removido de ${SHORT_DAY_NAMES[target]}`;

  void showUndoToast(label, () => {
    setTargetTimes(target, previous);
  });
};

const toggleAccordionDay = (day: number) => {
  expandedDay.value = expandedDay.value === day ? null : day;
};

const openCopyToDays = async (sourceDay: number) => {
  const otherDays = sortedActiveDays.value.filter((d) => d !== sourceDay);
  if (otherDays.length === 0) return;

  const sourceTimes = [...(props.modelValue.perDay[sourceDay] ?? [])];
  await alertRef.value?.setOpen(`Copiar ${SHORT_DAY_NAMES[sourceDay]} para…`, '', {
    inputs: otherDays.map((day) => ({
      type: 'checkbox' as const,
      label: SHORT_DAY_NAMES[day],
      value: String(day),
      checked: false,
    })),
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      {
        text: 'Copiar',
        handler: (selectedValues: string[]) => {
          if (!selectedValues || selectedValues.length === 0) return;
          const nextPerDay = { ...props.modelValue.perDay };
          for (const val of selectedValues) {
            nextPerDay[Number(val)] = [...sourceTimes];
          }
          updateState({ perDay: nextPerDay });
          void triggerHaptic('light');
          void showToast('success', 'Horários copiados!');
        },
      },
    ],
  });
};
</script>

<template>
  <ion-modal
    :is-open="isOpen"
    :breakpoints="[0, 0.65, 0.9]"
    :initial-breakpoint="0.65"
    handle-behavior="cycle"
    aria-label="Configurar lembretes"
    class="reminder-bottom-sheet"
    @did-present="handleSheetDidPresent"
    @did-dismiss="handleSheetDidDismiss"
  >
    <div class="sheet-shell">
      <div
        ref="sheetHeaderRef"
        tabindex="-1"
        class="sheet-header"
      >
        <BaseSegment
          :model-value="modelValue.mode"
          :segments="modeSegments"
          @update:model-value="switchMode($event as ReminderMode)"
        />
      </div>

      <div class="sheet-body">
        <div v-if="modelValue.mode === 'all'" class="sheet-section">
          <div class="section-header-row">
            <span class="section-subtitle">Horários</span>
            <span v-if="modelValue.times.length >= 5" class="limit-counter">
              {{ modelValue.times.length }}/{{ MAX_REMINDERS_PER_DAY }}
            </span>
          </div>

          <ReminderTimePills
            :times="modelValue.times"
            @edit="openTimePicker('all', $event)"
            @remove="removeTime('all', $event)"
            @add="openTimePicker('all')"
          />

          <p v-if="modelValue.times.length >= MAX_REMINDERS_PER_DAY" class="limit-reached-note">
            Limite máximo de {{ MAX_REMINDERS_PER_DAY }} horários por dia atingido.
          </p>
        </div>

        <div v-else class="per-day-list">
          <ReminderDayAccordion
            v-for="day in sortedActiveDays"
            :key="day"
            :day="day"
            :times="modelValue.perDay[day] ?? []"
            :is-expanded="expandedDay === day"
            :can-copy="sortedActiveDays.length > 1"
            @toggle="toggleAccordionDay"
            @edit-time="openTimePicker"
            @remove-time="removeTime"
            @add-time="openTimePicker"
            @copy="openCopyToDays"
          />
        </div>
      </div>

      <div class="sheet-footer">
        <span class="weekly-total-label">
          {{ totalWeeklyCount }} {{ totalWeeklyCount === 1 ? 'lembrete' : 'lembretes' }} por semana
        </span>
        <BaseButton color="primary" @click="emit('update:isOpen', false)">
          Concluir
        </BaseButton>
      </div>
    </div>

    <ReminderTimePickerModal
      v-model:is-open="isTimePickerOpen"
      :target="pickerTarget"
      :initial-time="editingOriginalTime"
      @confirm="confirmTimePicker"
    />

    <BaseAlert ref="alertRef" />
  </ion-modal>
</template>

<style scoped>
.reminder-bottom-sheet {
  --background: var(--color-background-secondary);
  --border-radius: 20px 20px 0 0;
}

.sheet-shell {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  background: var(--color-background-secondary);
  color: var(--color-text-primary);
}

.sheet-header {
  padding: 20px 20px 12px;
  outline: none;
}

.sheet-body {
  overflow-y: auto;
  padding: 8px 20px 16px;
}

.sheet-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-subtitle {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.limit-counter {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-secondary);
}

.limit-reached-note {
  margin: 0;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.per-day-list {
  display: flex;
  flex-direction: column;
}

.sheet-footer {
  padding: 0 20px calc(16px + var(--ion-safe-area-bottom));
  background: var(--color-background-secondary);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.weekly-total-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}
</style>
