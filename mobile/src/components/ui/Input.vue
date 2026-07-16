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

const emit = defineEmits(['update:modelValue', 'blur']);

const updateValue = (event) => {
  emit('update:modelValue', event.detail.value);
};

// O Input.vue tem múltiplos elementos raiz, então o Vue não repassa
// automaticamente um @blur do componente pai para o ion-input interno.
// Por isso emitimos o evento manualmente aqui.
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

// Quem decide QUANDO existe erro é o pai (via Vuelidate: .$touch() no
// blur e .$validate() no submit). Aqui só refletimos se há mensagem.
const isInvalid = computed(() => !!props.errorText);
</script>

<template>
  <ion-label>
    {{ label }}
  </ion-label>

  <div class="input-container">
    <ion-input
      mode="ios"
      :type="inputType"
      :value="modelValue"
      :placeholder="placeholder"
      :class="{ 'has-error': isInvalid }"
      @ion-input="updateValue"
      @ion-blur="handleBlur"
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
  margin-top: 1.5rem;
  margin-bottom: 1rem;
}

ion-input {
  width: 100%;
  border-radius: var(--border-radius-default);
  background: var(--color-background-secondary);

  --color: var(--color-text-primary);
  --placeholder-color: var(--placeholder);
  --placeholder-opacity: .8;
  --padding-top: 1.125rem;
  --padding-bottom: 1.125rem;
  --padding-start: 1rem;
  --padding-end: 1rem;

  /* Remove a linha nativa que o Ionic desenha abaixo do input quando
     helper/error text ou counter estão presentes. */
  --border-width: 0;
  --border-color: transparent;
}

ion-input:focus-within {
  box-shadow: 0 0 0 2px var(--color-primary);
}

ion-input.has-error {
  box-shadow: 0 0 0 2px var(--color-danger, #eb445a);
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