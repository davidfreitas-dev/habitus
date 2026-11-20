<script setup lang="ts">
import { IonIcon } from '@ionic/vue';
import { addOutline, closeOutline, copyOutline, timeOutline } from 'ionicons/icons';
import { MAX_REMINDERS_PER_DAY } from '@/utils/habitUtils';

withDefaults(
  defineProps<{
    times: string[];
    dayLabel?: string;
    interactive?: boolean;
    showCopy?: boolean;
    showLimitInline?: boolean;
  }>(),
  {
    dayLabel: undefined,
    interactive: true,
    showCopy: false,
    showLimitInline: false,
  }
);

const emit = defineEmits<{
  (e: 'edit', time: string): void;
  (e: 'remove', time: string): void;
  (e: 'add'): void;
  (e: 'copy'): void;
}>();
</script>

<template>
  <div class="chips-grid">
    <div
      v-for="time in times"
      :key="time"
      class="time-pill"
    >
      <button
        type="button"
        class="time-pill__edit"
        :tabindex="interactive ? 0 : -1"
        :aria-label="dayLabel ? `Editar horário ${time} de ${dayLabel}` : `Editar horário ${time}`"
        @click="emit('edit', time)"
      >
        <ion-icon :icon="timeOutline" class="time-pill__clock" />
        <span>{{ time }}</span>
      </button>
      <button
        type="button"
        class="time-pill__remove"
        :tabindex="interactive ? 0 : -1"
        :aria-label="`Remover ${time}`"
        @click="emit('remove', time)"
      >
        <span class="time-pill__remove-dot">
          <ion-icon :icon="closeOutline" />
        </span>
      </button>
    </div>

    <button
      type="button"
      class="add-time-pill"
      :tabindex="interactive ? 0 : -1"
      :disabled="times.length >= MAX_REMINDERS_PER_DAY"
      @click="emit('add')"
    >
      <ion-icon :icon="addOutline" />
      <span>Horário</span>
    </button>

    <button
      v-if="showCopy"
      type="button"
      class="copy-pill"
      :tabindex="interactive ? 0 : -1"
      @click="emit('copy')"
    >
      <ion-icon :icon="copyOutline" />
      <span>Copiar para…</span>
    </button>
  </div>

  <div
    v-if="showLimitInline && times.length >= 5"
    class="day-limit-row"
  >
    <span class="limit-counter">
      {{ times.length }}/{{ MAX_REMINDERS_PER_DAY }}
    </span>
    <span
      v-if="times.length >= MAX_REMINDERS_PER_DAY"
      class="limit-reached-note"
    >
      Limite máximo de {{ MAX_REMINDERS_PER_DAY }} horários por dia atingido.
    </span>
  </div>
</template>

<style scoped>
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
</style>
