<script setup>
import { IonButton, IonSpinner } from '@ionic/vue';

const props = defineProps({
  isDisabled: Boolean,
  isLoading: Boolean,
  color: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'danger', 'outline'].includes(value),
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'auto', 'small'].includes(value),
  },
});
</script>

<template>
  <ion-button
    mode="md"
    :class="[color, size]"
    :disabled="isLoading || isDisabled"
  >
    <ion-spinner v-if="isLoading" name="dots" />
    <slot v-else />
  </ion-button>
</template>

<style scoped>
ion-button {
  width: 100%;
  height: 3.5rem;
  font-size: 1rem;
  font-weight: 700;
  text-transform: unset;
  letter-spacing: .0225rem;

  --padding-start: 1.5rem;
  --padding-end: 1.5rem;

  --color: var(--color-background-primary);
  --background: var(--color-text-primary);
  --background-hover: var(--color-text-primary);
  --background-activated: var(--color-text-primary);
  --background-focused: var(--color-text-primary);
  --border-radius: var(--border-radius-default);
}

/* Padrão */
ion-button.default {
  width: 100%;
  height: 3.5rem;
}

/* Mesma altura, largura automática */
ion-button.auto {
  width: auto;
  min-width: fit-content;
  height: 3.5rem;
}

/* Botão compacto */
ion-button.small {
  width: auto;
  min-width: fit-content;
  height: 2.5rem;

  font-size: 0.875rem;

  --padding-start: 1rem;
  --padding-end: 1rem;
}

ion-button.primary {
  --color: var(--color-neutral-900);
  --background: var(--color-primary);
  --background-hover: var(--color-primary-hover);
  --background-activated: var(--color-primary-focus);
  --background-focused: var(--color-primary-focus);
  --border-radius: var(--border-radius-default);
}

ion-button.danger {
  --color: #fff;
  --background: var(--color-danger);
  --background-hover: var(--color-danger-hover);
  --background-activated: var(--color-danger-focus);
  --background-focused: var(--color-danger-focus);
  --border-radius: var(--border-radius-default);
}

ion-button.outline {    
  --color: var(--color-text-primary);
  --background: transparent;
  --background-hover: transparent;
  --background-activated: transparent;
  --background-focused: transparent;
  --border-radius: var(--border-radius-default);
  --border-color: var(--color-primary-accent);
  --border-style: solid;
  --border-width: 1px;
}
</style>