<script setup lang="ts">
import { computed } from 'vue';
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
  <div class="onboarding-step" tabindex="-1">
    <h4 class="onboarding-step__title">
      {{ stepContent.title }}
    </h4>
    <p class="onboarding-step__description">
      {{ stepContent.description }}
    </p>
    <div class="onboarding-step__footer">
      <button
        type="button"
        tabindex="0"
        class="onboarding-step__skip"
        @click="emit('skip')"
      >
        Pular
      </button>
      <div class="onboarding-step__right">
        <span v-if="total > 1" class="onboarding-step__counter">
          {{ index + 1 }} de {{ total }}
        </span>
        <button
          type="button"
          tabindex="0"
          class="onboarding-step__next"
          @click="handleNext"
        >
          {{ isLast ? 'Entendi' : 'Próximo' }}
        </button>
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
  outline: none;
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
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  padding: 6px 8px;
  font-size: 13px;
  font-weight: 600;
  font-family: inherit;
  text-transform: none;
  margin: 0;
  cursor: pointer;
  border-radius: var(--radius-md);
  transition: color 0.15s ease, background-color 0.15s ease;
  outline: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.onboarding-step__skip:hover,
.onboarding-step__skip:focus-visible {
  color: var(--color-text-primary);
  background: rgba(255, 255, 255, 0.05);
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
  background: var(--color-primary);
  border: none;
  color: var(--color-neutral-900);
  border-radius: var(--radius-md);
  padding: 6px 16px;
  font-size: 13px;
  font-weight: 700;
  font-family: inherit;
  text-transform: none;
  margin: 0;
  cursor: pointer;
  transition: background-color 0.15s ease, opacity 0.15s ease;
  outline: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.onboarding-step__next:hover,
.onboarding-step__next:focus-visible {
  background: var(--color-primary-hover);
}

.onboarding-step__next:active {
  background: var(--color-primary-focus);
}
</style>
