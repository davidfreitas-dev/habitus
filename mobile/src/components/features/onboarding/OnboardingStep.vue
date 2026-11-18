<script setup lang="ts">
import { computed } from 'vue';
import { IonButton } from '@ionic/vue';
import type { StepEntity } from 'v-onboarding';

interface StepContent {
  title?: string;
  description?: string;
}

const props = withDefaults(
  defineProps<{
    step: StepEntity;
    index: number;
    isLast?: boolean;
    total?: number;
  }>(),
  {
    isLast: false,
    total: 1,
  }
);

const stepContent = computed<StepContent>(() => {
  return (props.step.content as StepContent | undefined) ?? {};
});

const emit = defineEmits<{
  (e: 'next'): void;
  (e: 'skip'): void;
}>();

const handleNext = () => {
  emit('next');
};
</script>

<template>
  <div class="onboarding-step">
    <h4 class="onboarding-step__title">
      {{ stepContent.title }}
    </h4>
    <p class="onboarding-step__description">
      {{ stepContent.description }}
    </p>
    <div class="onboarding-step__footer">
      <ion-button
        fill="clear"
        size="small"
        class="onboarding-step__skip"
        @click="emit('skip')"
      >
        Pular
      </ion-button>
      <div class="onboarding-step__right">
        <span v-if="total > 1" class="onboarding-step__counter">
          {{ index + 1 }} de {{ total }}
        </span>
        <ion-button
          size="small"
          class="onboarding-step__next"
          @click="handleNext"
        >
          {{ isLast ? 'Entendi' : 'Próximo' }}
        </ion-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.onboarding-step {
  min-width: 240px;
  background: var(--color-background-secondary);
  border-radius: var(--radius-xl);
  padding: 20px 24px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 320px;
  border: 1px solid var(--color-primary);
}

.onboarding-step__title {
  color: var(--color-primary);
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 6px 0;
}

.onboarding-step__description {
  color: var(--color-text-accent);
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 16px 0;
}

.onboarding-step__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.onboarding-step__skip {
  --color: var(--color-text-secondary);
  --padding-start: 0;
  --padding-end: 0;
  font-size: 13px;
  font-weight: 600;
  text-transform: none;
  margin: 0;
}

.onboarding-step__right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.onboarding-step__counter {
  color: var(--color-text-secondary);
  font-size: 12px;
  font-weight: 600;
}

.onboarding-step__next {
  --background: var(--color-primary);
  --background-hover: var(--color-primary-hover);
  --background-activated: var(--color-primary-focus);
  --color: var(--color-neutral-900);
  --border-radius: var(--radius-md);
  --padding-start: 16px;
  --padding-end: 16px;
  font-size: 13px;
  font-weight: 700;
  text-transform: none;
  margin: 0;
}
</style>
