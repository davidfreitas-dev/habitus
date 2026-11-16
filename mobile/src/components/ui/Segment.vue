<template>
  <ion-segment
    mode="md"
    :key="modelValue"
    :value="modelValue"
    @ion-change="$emit('update:modelValue', ($event.detail.value as string))"
  >
    <ion-segment-button 
      v-for="segment in segments" 
      :key="segment.value" 
      :value="segment.value"
    >
      <ion-label>{{ segment.label }}</ion-label>
    </ion-segment-button>
  </ion-segment>
</template>

<script setup lang="ts">
import { IonSegment, IonSegmentButton, IonLabel } from '@ionic/vue';

export interface SegmentItem {
  label: string;
  value: string;
}

withDefaults(
  defineProps<{
    modelValue: string;
    segments?: SegmentItem[];
  }>(),
  {
    segments: () => [],
  }
);

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();
</script>

<style scoped>
ion-segment {
  position: sticky;
  top: 0;
  z-index: 10;
  padding: 0 .5rem;
  background: var(--color-background-primary);
}

ion-segment-button::part(indicator-background) {
  background: var(--color-primary);
}

/* Material Design styles */
ion-segment-button.md::part(native) {
  font-weight: 600;
  color: var(--color-text-primary);
}

.segment-button-checked.md::part(native) {
  color: var(--color-primary);
}

ion-segment-button.md::part(indicator-background) {
  height: 4px;
}

/* iOS styles */
ion-segment-button.ios::part(native) {
  color: var(--color-primary);
}

.segment-button-checked.ios::part(native) {
  color: var(--color-text-primary);
}

ion-segment-button.ios::part(indicator-background) {
  border-radius: 20px;
}
</style>
