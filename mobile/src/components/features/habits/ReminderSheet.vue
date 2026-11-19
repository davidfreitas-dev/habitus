<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import {
  IonDatetime,
  IonIcon,
  IonModal,
} from '@ionic/vue';
import {
  addOutline,
  chevronForwardOutline,
  closeOutline,
  copyOutline,
  timeOutline,
} from 'ionicons/icons';
import {
  MAX_REMINDERS_PER_DAY,
  SHORT_DAY_NAMES,
  formatReminderTime,
  timeStringToLocalISO,
} from '@/utils/habitUtils';
import { useHaptics } from '@/composables/useHaptics';
import { useToast } from '@/composables/useToast';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseSegment, { type SegmentItem } from '@/components/base/BaseSegment.vue';
import BaseAlert from '@/components/base/BaseAlert.vue';

export type ReminderMode = 'all' | 'perDay';

const modeSegments: SegmentItem[] = [
  { label: 'Todos os dias', value: 'all' },
  { label: 'Por dia', value: 'perDay' },
];

export interface ReminderScheduleState {
  enabled: boolean;
  mode: ReminderMode;
  times: string[];
  perDay: Record<number, string[]>;
}

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
const pickerTime = ref<string>(timeStringToLocalISO('09:00'));
const pickerTarget = ref<number | 'all'>('all');
const editingOriginalTime = ref<string | null>(null);

const sortedActiveDays = computed(() => [...props.activeWeekDays].sort((a, b) => a - b));

const minutePresets = ['00', '15', '30', '45'] as const;

const currentPickerFormatted = computed(() => formatReminderTime(pickerTime.value) ?? '09:00');
const currentPickerMinute = computed(() => currentPickerFormatted.value.split(':')[1] ?? '00');

const pickerPreviewLabel = computed(() => {
  const time = currentPickerFormatted.value;
  if (pickerTarget.value === 'all') {
    return `Lembrete diário às ${time}`;
  }
  return `Lembrete (${SHORT_DAY_NAMES[pickerTarget.value]}) às ${time}`;
});

const applyMinutePreset = (minute: string) => {
  const [hour] = currentPickerFormatted.value.split(':');
  pickerTime.value = timeStringToLocalISO(`${hour}:${minute}`);
  void triggerHaptic('light');
};

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

  // Switching from perDay -> all
  const mergePerDayToAll = () => {
    const merged = new Set<string>();
    for (const day of sortedActiveDays.value) {
      for (const time of props.modelValue.perDay[day] ?? []) {
        merged.add(time);
      }
    }
    const nextTimes = Array.from(merged).sort().slice(0, MAX_REMINDERS_PER_DAY);
    updateState({ mode: 'all', times: nextTimes });
  };

  if (hasDivergentPerDay()) {
    await alertRef.value?.setOpen(
      'Unificar horários?',
      'Você tem horários diferentes por dia. Ao voltar para "Mesmo horário todo dia", eles serão unificados em uma lista única.',
      {
        buttons: [
          { text: 'Cancelar', role: 'cancel' },
          {
            text: 'Unificar',
            handler: () => {
              mergePerDayToAll();
            },
          },
        ],
      }
    );
    return;
  }

  mergePerDayToAll();
};

const openTimePicker = (target: number | 'all', existingTime: string | null = null) => {
  const list = target === 'all' ? props.modelValue.times : (props.modelValue.perDay[target] ?? []);
  if (!existingTime && list.length >= MAX_REMINDERS_PER_DAY) {
    void showToast('info', `Limite máximo de ${MAX_REMINDERS_PER_DAY} horários por dia.`);
    return;
  }

  pickerTarget.value = target;
  editingOriginalTime.value = existingTime;
  pickerTime.value = timeStringToLocalISO(existingTime ?? '09:00');
  isTimePickerOpen.value = true;
};

const confirmTimePicker = () => {
  const formatted = formatReminderTime(pickerTime.value);
  if (!formatted) {
    isTimePickerOpen.value = false;
    return;
  }

  const original = editingOriginalTime.value;

  if (pickerTarget.value === 'all') {
    const base = original
      ? props.modelValue.times.filter((t) => t !== original)
      : props.modelValue.times;

    if (base.includes(formatted)) {
      void showToast('info', 'Este horário já foi adicionado.');
      return;
    }
    if (base.length >= MAX_REMINDERS_PER_DAY) {
      void showToast('info', `Limite máximo de ${MAX_REMINDERS_PER_DAY} horários por dia.`);
      return;
    }

    updateState({ times: [...base, formatted].sort() });
  } else {
    const day = pickerTarget.value;
    const current = props.modelValue.perDay[day] ?? [];
    const base = original ? current.filter((t) => t !== original) : current;

    if (base.includes(formatted)) {
      void showToast('info', 'Este horário já foi adicionado neste dia.');
      return;
    }
    if (base.length >= MAX_REMINDERS_PER_DAY) {
      void showToast('info', `Limite máximo de ${MAX_REMINDERS_PER_DAY} horários por dia.`);
      return;
    }

    updateState({
      perDay: {
        ...props.modelValue.perDay,
        [day]: [...base, formatted].sort(),
      },
    });
  }

  void triggerHaptic('light');
  editingOriginalTime.value = null;
  isTimePickerOpen.value = false;
};

const removeTime = (target: number | 'all', time: string) => {
  void triggerHaptic('light');

  if (target === 'all') {
    const previous = [...props.modelValue.times];
    updateState({ times: previous.filter((t) => t !== time) });
    void showUndoToast(`Horário ${time} removido`, () => {
      updateState({ times: previous });
    });
    return;
  }

  const previousDayTimes = [...(props.modelValue.perDay[target] ?? [])];
  updateState({
    perDay: {
      ...props.modelValue.perDay,
      [target]: previousDayTimes.filter((t) => t !== time),
    },
  });
  void showUndoToast(`Horário ${time} removido de ${SHORT_DAY_NAMES[target]}`, () => {
    updateState({
      perDay: {
        ...props.modelValue.perDay,
        [target]: previousDayTimes,
      },
    });
  });
};

const formatDaySummary = (day: number): string => {
  const list = props.modelValue.perDay[day] ?? [];
  if (list.length === 0) return 'Sem lembrete';
  return list.join(' · ');
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
        <!-- Mode: Same time every day -->
        <div v-if="modelValue.mode === 'all'" class="sheet-section">
          <div class="section-header-row">
            <span class="section-subtitle">Horários</span>
            <span v-if="modelValue.times.length >= 5" class="limit-counter">
              {{ modelValue.times.length }}/{{ MAX_REMINDERS_PER_DAY }}
            </span>
          </div>

          <div class="chips-grid">
            <div
              v-for="time in modelValue.times"
              :key="time"
              class="time-pill"
            >
              <button
                type="button"
                class="time-pill__edit"
                :aria-label="`Editar horário ${time}`"
                @click="openTimePicker('all', time)"
              >
                <ion-icon :icon="timeOutline" class="time-pill__clock" />
                <span>{{ time }}</span>
              </button>
              <button
                type="button"
                class="time-pill__remove"
                :aria-label="`Remover ${time}`"
                @click="removeTime('all', time)"
              >
                <span class="time-pill__remove-dot">
                  <ion-icon :icon="closeOutline" />
                </span>
              </button>
            </div>

            <button
              type="button"
              class="add-time-pill"
              :disabled="modelValue.times.length >= MAX_REMINDERS_PER_DAY"
              @click="openTimePicker('all')"
            >
              <ion-icon :icon="addOutline" />
              <span>Horário</span>
            </button>
          </div>

          <p v-if="modelValue.times.length >= MAX_REMINDERS_PER_DAY" class="limit-reached-note">
            Limite máximo de {{ MAX_REMINDERS_PER_DAY }} horários por dia atingido.
          </p>
        </div>

        <!-- Mode: Customize per day -->
        <div v-else class="per-day-list">
          <div
            v-for="day in sortedActiveDays"
            :key="day"
            class="day-accordion-item"
          >
            <button
              type="button"
              class="day-accordion-trigger"
              :aria-expanded="expandedDay === day"
              @click="toggleAccordionDay(day)"
            >
              <span class="day-accordion-trigger__name">{{ SHORT_DAY_NAMES[day] }}</span>
              <span
                class="day-accordion-trigger__summary"
                :class="{ 'day-accordion-trigger__summary--empty': (modelValue.perDay[day]?.length ?? 0) === 0 }"
              >
                {{ formatDaySummary(day) }}
              </span>
              <ion-icon
                :icon="chevronForwardOutline"
                class="day-accordion-trigger__chevron"
                :class="{ 'day-accordion-trigger__chevron--open': expandedDay === day }"
              />
            </button>

            <div
              class="day-accordion-collapse"
              :class="{ 'day-accordion-collapse--open': expandedDay === day }"
              :aria-hidden="expandedDay !== day"
            >
              <div class="day-accordion-collapse__inner">
                <div class="day-accordion-panel">
                  <div class="chips-grid">
                    <div
                      v-for="time in (modelValue.perDay[day] ?? [])"
                      :key="`${day}-${time}`"
                      class="time-pill"
                    >
                      <button
                        type="button"
                        class="time-pill__edit"
                        :tabindex="expandedDay === day ? 0 : -1"
                        :aria-label="`Editar horário ${time} de ${SHORT_DAY_NAMES[day]}`"
                        @click="openTimePicker(day, time)"
                      >
                        <ion-icon :icon="timeOutline" class="time-pill__clock" />
                        <span>{{ time }}</span>
                      </button>
                      <button
                        type="button"
                        class="time-pill__remove"
                        :tabindex="expandedDay === day ? 0 : -1"
                        :aria-label="`Remover ${time}`"
                        @click="removeTime(day, time)"
                      >
                        <span class="time-pill__remove-dot">
                          <ion-icon :icon="closeOutline" />
                        </span>
                      </button>
                    </div>

                    <button
                      type="button"
                      class="add-time-pill"
                      :tabindex="expandedDay === day ? 0 : -1"
                      :disabled="(modelValue.perDay[day]?.length ?? 0) >= MAX_REMINDERS_PER_DAY"
                      @click="openTimePicker(day)"
                    >
                      <ion-icon :icon="addOutline" />
                      <span>Horário</span>
                    </button>

                    <button
                      v-if="sortedActiveDays.length > 1 && (modelValue.perDay[day]?.length ?? 0) > 0"
                      type="button"
                      class="copy-pill"
                      :tabindex="expandedDay === day ? 0 : -1"
                      @click="openCopyToDays(day)"
                    >
                      <ion-icon :icon="copyOutline" />
                      <span>Copiar para…</span>
                    </button>
                  </div>

                  <div
                    v-if="(modelValue.perDay[day]?.length ?? 0) >= 5"
                    class="day-limit-row"
                  >
                    <span class="limit-counter">
                      {{ modelValue.perDay[day]?.length }}/{{ MAX_REMINDERS_PER_DAY }}
                    </span>
                    <span
                      v-if="(modelValue.perDay[day]?.length ?? 0) >= MAX_REMINDERS_PER_DAY"
                      class="limit-reached-note"
                    >
                      Limite máximo de {{ MAX_REMINDERS_PER_DAY }} horários por dia atingido.
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
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

    <!-- Time picker bottom sheet -->
    <ion-modal
      :is-open="isTimePickerOpen"
      :breakpoints="[0, 0.65, 0.9]"
      :initial-breakpoint="0.65"
      handle-behavior="cycle"
      aria-label="Selecionar horário"
      class="time-picker-sheet"
      @did-dismiss="isTimePickerOpen = false"
    >
      <div class="time-picker-modal">
        <div class="time-picker-header">
          <button
            type="button"
            class="time-picker-close-btn"
            aria-label="Fechar seletor de horário"
            @click="isTimePickerOpen = false"
          >
            <ion-icon :icon="closeOutline" />
          </button>
        </div>

        <div class="time-picker-center">
          <div class="time-picker-wheel-wrap">
            <ion-datetime
              v-model="pickerTime"
              mode="ios"
              presentation="time"
              locale="pt-BR"
              class="time-picker-wheel"
            />
            <span class="time-picker-colon" aria-hidden="true">:</span>
          </div>

          <div
            class="minute-presets"
            role="group"
            aria-label="Minutos rápidos"
          >
            <button
              v-for="min in minutePresets"
              :key="min"
              type="button"
              class="minute-preset-pill"
              :class="{ 'minute-preset-pill--active': currentPickerMinute === min }"
              @click="applyMinutePreset(min)"
            >
              :{{ min }}
            </button>
          </div>

          <p class="time-picker-preview">
            {{ pickerPreviewLabel }}
          </p>
        </div>

        <div class="time-picker-actions">
          <BaseButton
            color="primary"
            class="picker-confirm-btn"
            @click="confirmTimePicker"
          >
            {{ editingOriginalTime ? 'Salvar' : 'Adicionar' }}
          </BaseButton>
        </div>
      </div>
    </ion-modal>

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

.chips-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.time-pill {
  display: inline-flex;
  align-items: center;
  height: 36px;
  padding-left: 10px;
  padding-right: 4px;
  border-radius: var(--radius-full);
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-default);
}

.time-pill__edit {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 36px;
  padding: 0 2px 0 0;
  border: none;
  background: transparent;
  color: var(--color-text-primary);
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

.time-pill__clock {
  font-size: 0.9rem;
  color: var(--color-success-accent);
}

.time-pill__remove {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 36px;
  border: none;
  background: transparent;
  cursor: pointer;
}

.time-pill__remove::after {
  content: '';
  position: absolute;
  inset: -4px -8px;
}

.time-pill__remove-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: var(--radius-full);
  background: var(--color-border-strong);
  color: var(--color-text-primary);
  font-size: 0.75rem;
}

.add-time-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 36px;
  padding: 0 16px;
  border-radius: var(--radius-full);
  border: 1px dashed var(--color-success);
  background: transparent;
  color: var(--color-success);
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

:global(body.dark) .add-time-pill {
  color: var(--color-success-accent);
}

.add-time-pill:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.limit-reached-note {
  margin: 0;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

/* Per-day accordion */
.per-day-list {
  display: flex;
  flex-direction: column;
}

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

.copy-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 36px;
  padding: 0 12px;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border-default);
  background: transparent;
  color: var(--color-success-accent);
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

.day-limit-row {
  display: flex;
  align-items: center;
  gap: 8px;
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

ion-modal.time-picker-sheet {
  --background: var(--color-background-secondary);
  --border-radius: 24px 24px 0 0;
  --backdrop-opacity: 0.7;
}

.time-picker-modal {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 20px calc(20px + var(--ion-safe-area-bottom));
  background: var(--color-background-secondary);
  width: 100%;
}

.time-picker-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.time-picker-header {
  display: flex;
  justify-content: flex-end;
  width: 100%;
  margin-bottom: 0;
  z-index: 2;
}

.time-picker-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  min-height: 44px;
  margin-right: -8px;
  border: none;
  background: transparent;
  color: var(--color-text-secondary);
  font-size: 1.5rem;
  cursor: pointer;
}

.time-picker-wheel-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 340px;
  overflow: hidden;
}

.time-picker-wheel-wrap::before,
.time-picker-wheel-wrap::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  height: 28px;
  pointer-events: none;
  z-index: 2;
}

.time-picker-wheel-wrap::before {
  top: 0;
  background: linear-gradient(to bottom, var(--color-background-secondary) 25%, transparent);
}

.time-picker-wheel-wrap::after {
  bottom: 0;
  background: linear-gradient(to top, var(--color-background-secondary) 25%, transparent);
}

.time-picker-wheel {
  width: 100%;
  min-width: 280px;
  --background: var(--color-background-secondary);
  --background-rgb: var(--color-background-secondary-rgb);
  --wheel-fade-background-rgb: var(--color-background-secondary-rgb);
  --wheel-highlight-background: var(--color-background-elevated);
  --wheel-highlight-border-radius: var(--radius-lg);
}

.time-picker-colon {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -54%);
  font-size: 1.875rem;
  font-weight: 800;
  color: var(--color-primary);
  pointer-events: none;
  z-index: 3;
}

.time-picker-wheel::part(wheel-item) {
  color: var(--color-text-secondary);
  font-size: 1.5rem;
  font-weight: 600;
}

.time-picker-wheel::part(wheel-item active) {
  color: var(--color-primary);
  font-size: 1.875rem;
  font-weight: 800;
}

.minute-presets {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
}

.minute-preset-pill {
  min-width: 54px;
  height: 36px;
  padding: 0 12px;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border-default);
  background: transparent;
  color: var(--color-text-primary);
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}

.minute-preset-pill--active {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.time-picker-preview {
  margin: 16px 0 0;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-align: center;
}

.time-picker-actions {
  width: 100%;
  margin-top: 16px;
}

.picker-confirm-btn {
  width: 100%;
}
</style>
