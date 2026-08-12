<script setup>
import { ref, computed } from 'vue';
import { IonInput, IonLabel, IonIcon } from '@ionic/vue';
import { eyeOutline, eyeOffOutline } from 'ionicons/icons';

const props = defineProps({
  type: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  modelValue: {
    type: [ String, Number ],
    default: ''
  },
  errorText: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'keyup']);

const updateValue = (event) => {
  emit('update:modelValue', event.detail.value);
};

const handleBlur = (event) => {
  emit('blur', event);
};

const isPasswordVisible = ref(false);

const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value;
};

const inputType = computed(() => {
  if (props.type === 'password') {
    return isPasswordVisible.value ? 'text' : 'password';
  }
  return props.type;
});

const isInvalid = computed(() => !!props.errorText);
</script>

<template>
  <ion-label>
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

  /* Remove a linha nativa que o Ionic desenha abaixo/acima do input */
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