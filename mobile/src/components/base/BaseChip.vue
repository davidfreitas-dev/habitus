<script setup lang="ts">
import { IonChip, IonLabel, IonIcon, IonAvatar } from '@ionic/vue';

withDefaults(
  defineProps<{
    label?: string;
    isActive?: boolean;
    disabled?: boolean;
    outline?: boolean;
    color?: string;
    icon?: string;
    avatar?: string;
    mode?: 'ios' | 'md';
  }>(),
  {
    label: '',
    isActive: false,
    disabled: false,
    outline: false,
    color: undefined,
    icon: undefined,
    avatar: undefined,
    mode: 'md',
  }
);

defineEmits<{
  (e: 'click', event: MouseEvent): void;
}>();
</script>

<template>
  <ion-chip
    :mode="mode"
    :color="color"
    :outline="outline"
    :disabled="disabled"
    :class="{ active: isActive }"
    @click="$emit('click', $event)"
  >
    <slot name="start">
      <ion-icon v-if="icon" :icon="icon" />
      <ion-avatar v-if="avatar">
        <img :src="avatar" alt="">
      </ion-avatar>
    </slot>

    <slot>
      <ion-label v-if="label">
        {{ label }}
      </ion-label>
    </slot>

    <slot name="end" />
  </ion-chip>
</template>

<style scoped>
ion-chip {
  --background: var(--color-background-secondary);
  --color: var(--color-text-secondary);
  background: var(--color-background-secondary);
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-full);
  padding: 6px 12px !important;
  padding-inline-start: 12px !important;
  padding-inline-end: 12px !important;
  margin: 0 !important;
  margin-inline-start: 0 !important;
  margin-inline-end: 0 !important;
  height: auto;
  min-height: unset;
  min-width: unset;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8125rem;
  font-weight: 700;
  line-height: 1.15;
  white-space: nowrap;
  flex-shrink: 0;
  cursor: pointer;
  transition: all 0.15s ease;
}

ion-chip ion-label {
  font-weight: 700;
  font-size: 0.8125rem;
  line-height: 1.15;
  margin: 0;
  padding: 0;
  white-space: nowrap;
  overflow: visible;
  text-overflow: unset;
  text-align: center;
}

ion-chip.active {
  --background: var(--color-primary);
  --color: var(--color-neutral-900);
  background: var(--color-primary);
  color: var(--color-neutral-900);
  border-color: var(--color-primary);
}

ion-chip.active ion-label {
  color: var(--color-neutral-900);
  font-weight: 700;
}

ion-chip[disabled],
ion-chip.chip-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
