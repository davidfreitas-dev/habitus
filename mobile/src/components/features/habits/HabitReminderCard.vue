<script setup lang="ts">
import { IonIcon } from '@ionic/vue';
import { chevronForwardOutline, notificationsOutline } from 'ionicons/icons';
import BaseToggle from '@/components/base/BaseToggle.vue';

defineProps<{
  enabled: boolean;
  summaryText: string;
}>();

const emit = defineEmits<{
  (e: 'toggle', enabled: boolean): void;
  (e: 'open-sheet'): void;
}>();
</script>

<template>
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
        :checked="enabled"
        aria-label="Ativar lembretes"
        @update:checked="emit('toggle', $event)"
      />
    </div>

    <div class="reminder-card__divider" />

    <button
      type="button"
      class="reminder-card__summary-btn"
      :class="{ 'reminder-card__summary-btn--disabled': !enabled }"
      :disabled="!enabled"
      aria-label="Configurar horários de lembrete"
      @click="emit('open-sheet')"
    >
      <span class="reminder-card__summary-text">{{ summaryText }}</span>
      <ion-icon
        v-if="enabled"
        :icon="chevronForwardOutline"
        class="reminder-card__chevron"
      />
    </button>
  </div>
</template>

<style scoped>
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
