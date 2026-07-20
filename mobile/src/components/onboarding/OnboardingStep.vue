<script setup>
const props = defineProps({
  step: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  },
  isLast: {
    type: Boolean,
    default: false
  },
  total: {
    type: Number,
    default: 1
  }
});

const emit = defineEmits(['next', 'skip']);
</script>

<template>
  <div class="onboarding-step">
    <h4 class="onboarding-step__title">
      {{ step.content?.title }}
    </h4>
    <p class="onboarding-step__description">
      {{ step.content?.description }}
    </p>
    <div class="onboarding-step__footer">
      <button class="onboarding-step__skip" @click="emit('skip')">
        Pular
      </button>
      <div class="onboarding-step__right">
        <span v-if="total > 1" class="onboarding-step__counter">
          {{ index + 1 }} de {{ total }}
        </span>
        <button class="onboarding-step__next" @click="emit('next')">
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
  background: none;
  border: none;
  color: var(--color-text-secondary);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 6px 0;
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
  color: var(--color-neutral-900);
  border: none;
  border-radius: var(--radius-md);
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}
</style>
