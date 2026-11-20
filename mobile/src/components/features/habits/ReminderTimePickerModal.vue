<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { IonButton, IonDatetime, IonIcon, IonModal } from '@ionic/vue';
import { closeOutline } from 'ionicons/icons';
import { SHORT_DAY_NAMES, formatReminderTime, timeStringToLocalISO } from '@/utils/habitUtils';
import { useHaptics } from '@/composables/useHaptics';
import BaseButton from '@/components/base/BaseButton.vue';

const minutePresets = ['00', '15', '30', '45'] as const;

const props = defineProps<{
  isOpen: boolean;
  target: number | 'all';
  initialTime?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void;
  (e: 'confirm', formattedTime: string): void;
}>();

const { triggerHaptic } = useHaptics();
const pickerTime = ref<string>(timeStringToLocalISO('09:00'));

watch(
  () => [props.isOpen, props.initialTime] as const,
  ([open, initial]) => {
    if (open) {
      pickerTime.value = timeStringToLocalISO(initial ?? '09:00');
    }
  },
  { immediate: true }
);

const currentPickerFormatted = computed(() => formatReminderTime(pickerTime.value) ?? '09:00');
const currentPickerMinute = computed(() => currentPickerFormatted.value.split(':')[1] ?? '00');

const pickerPreviewLabel = computed(() => {
  const time = currentPickerFormatted.value;
  if (props.target === 'all') {
    return `Lembrete diário às ${time}`;
  }
  return `Lembrete (${SHORT_DAY_NAMES[props.target]}) às ${time}`;
});

const applyMinutePreset = (minute: string) => {
  const [hour] = currentPickerFormatted.value.split(':');
  pickerTime.value = timeStringToLocalISO(`${hour}:${minute}`);
  void triggerHaptic('light');
};

const handleConfirm = () => {
  const formatted = formatReminderTime(pickerTime.value);
  if (!formatted) {
    emit('update:isOpen', false);
    return;
  }
  emit('confirm', formatted);
};
</script>

<template>
  <ion-modal
    :is-open="isOpen"
    :breakpoints="[0, 0.65, 0.9]"
    :initial-breakpoint="0.65"
    handle-behavior="cycle"
    aria-label="Selecionar horário"
    class="time-picker-sheet"
    @did-dismiss="emit('update:isOpen', false)"
  >
    <div class="time-picker-modal">
      <div class="time-picker-header">
        <ion-button
          fill="clear"
          class="time-picker-close-btn"
          aria-label="Fechar seletor de horário"
          @click="emit('update:isOpen', false)"
        >
          <ion-icon slot="icon-only" :icon="closeOutline" />
        </ion-button>
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
          @click="handleConfirm"
        >
          {{ initialTime ? 'Salvar' : 'Adicionar' }}
        </BaseButton>
      </div>
    </div>
  </ion-modal>
</template>

<style scoped>
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
  --color: var(--color-text-secondary);
  --padding-start: 8px;
  --padding-end: 8px;
  min-width: 44px;
  min-height: 44px;
  margin-right: -8px;
  font-size: 1.5rem;
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
