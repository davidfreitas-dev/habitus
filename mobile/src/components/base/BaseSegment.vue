<template>
  <ion-segment
    mode="ios"
    :value="modelValue"
    @ion-change="handleSegmentChange"
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
import { IonSegment, IonSegmentButton, IonLabel, type SegmentCustomEvent } from '@ionic/vue';

export interface SegmentItem {
  label: string;
  value: string;
}

const props = withDefaults(
  defineProps<{
    modelValue: string;
    segments?: SegmentItem[];
  }>(),
  {
    segments: () => [],
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const handleSegmentChange = (event: SegmentCustomEvent) => {
  const nextValue = event.detail.value;
  if (typeof nextValue === 'string' && nextValue !== props.modelValue) {
    emit('update:modelValue', nextValue);
  }
};
</script>

<style scoped>
ion-segment.ios {
  --background: var(--color-background-elevated);
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-full);
  padding: 4px;
  min-height: 48px;
}

ion-segment-button.ios {
  --color: var(--color-text-secondary);
  --color-checked: var(--color-neutral-900);
  --indicator-color: var(--color-primary);
  --indicator-box-shadow: none;
  --border-radius: var(--radius-full);
  --border-width: 0;
  --border-color: transparent;
  --padding-top: 6px;
  --padding-bottom: 6px;
  --padding-start: 10px;
  --padding-end: 10px;
  min-height: 40px;
  margin: 0;
  text-transform: none;
}

ion-segment-button.ios::part(native) {
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0;
}

ion-segment-button.ios::part(indicator-background) {
  background: var(--color-primary);
  border-radius: var(--radius-full);
  box-shadow: none;
}
</style>
