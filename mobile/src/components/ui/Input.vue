<script setup lang="ts">
import { ref, computed } from 'vue';
import { IonInput, IonLabel, IonIcon, type InputCustomEvent } from '@ionic/vue';
import { eyeOutline, eyeOffOutline } from 'ionicons/icons';

const props = withDefaults(
  defineProps<{
    type?: string;
    label?: string;
    placeholder?: string;
    modelValue?: string | number | null;
    errorText?: string;
  }>(),
  {
    type: 'text',
    label: '',
    placeholder: '',
    modelValue: '',
    errorText: '',
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null | undefined): void;
  (e: 'blur', event: any): void;
  (e: 'keyup', event: KeyboardEvent): void;
}>();

const updateValue = (event: InputCustomEvent) => {
  emit('update:modelValue', event.detail.value);
};

const handleBlur = (event: any) => {
  emit('blur', event);
};

const isPasswordVisible = ref(false);

const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value;
};

const inputType = computed<any>(() => {
  if (props.type === 'password') {
    return isPasswordVisible.value ? 'text' : 'password';
  }
  return props.type;
});

const isInvalid = computed(() => !!props.errorText);
</script>

<template>
  <ion-label v-if="label">
    {{ label }}
  </ion-label>

  <div class="input-container">
    <ion-input
      v-bind="$attrs"
      mode="ios"
      :type="inputType"
      :value="modelValue"
      :placeholder="placeholder"
      :class="{ 'has-error': isInvalid }"
      @ion-input="updateValue"
      @ion-blur="handleBlur"
      @keyup="$emit('keyup', $event)"
    />

    <ion-icon
      v-if="type === 'password'"
      :key="isPasswordVisible ? 'eye-off' : 'eye'"
      :icon="isPasswordVisible ? eyeOffOutline : eyeOutline"
      @click="togglePasswordVisibility"
    />
  </div>

  <p
    v-if="isInvalid"
    class="error-text"
    role="alert"
  >
    {{ errorText }}
  </p>
</template>

<style scoped>
ion-label {
  color: var(--color-text-primary);
  font-weight: 700;
  margin-top: 1.25rem;
  margin-bottom: .85rem;
}

ion-input {
  width: 100%;
  border: 1px solid var(--color-border-default);
  border-radius: var(--border-radius-default);
  background: var(--color-background-secondary);

  --color: var(--color-text-primary);
  --placeholder-color: var(--placeholder);
  --placeholder-opacity: .8;
  --padding-top: 1.125rem;
  --padding-bottom: 1.125rem;
  --padding-start: 1rem;
  --padding-end: 1rem;

  --border-width: 0 !important;
  --border-color: transparent !important;
  --border-style: none !important;
  --highlight-height: 0;
  --highlight-color-focused: transparent;
  --highlight-color-valid: transparent;
  --highlight-color-invalid: transparent;
}

ion-input.has-focus,
ion-input:focus-within {
  border-color: var(--color-primary);
  box-shadow: none !important;
}

ion-input.has-error {
  border-color: var(--color-danger, #eb445a);
  box-shadow: none !important;
}

.input-container {
  position: relative;
}

ion-icon {
  position: absolute;
  top: 50%;
  right: 1rem;
  transform: translateY(-50%);
  font-size: 1.5rem;
  color: var(--placeholder);
  cursor: pointer;
  z-index: 10;
}

.error-text {
  margin: 0.5rem 0 0;
  color: var(--color-danger, #eb445a);
  font-size: 0.8125rem;
}
</style>
