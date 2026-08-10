<template>
  <ion-row class="ion-justify-content-start ion-align-items-start" :class="size">
    <ion-checkbox
      mode="md"
      :checked="isChecked"
      :disabled="isDisabled"
      @ion-change="$emit('handleCheckboxChange')"
    />
    <ion-label :class="{ disabled: isDisabled }" @click="handleItem">
      <slot>{{ label }}</slot>
    </ion-label>
  </ion-row>
</template>

<script setup>
import { IonRow, IonCheckbox, IonLabel } from '@ionic/vue';

const props = defineProps({
  isChecked: Boolean,
  isDisabled: Boolean,
  label: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'default',
    validator: (val) => ['default', 'small'].includes(val)
  }
});

const emit = defineEmits(['handleCheckboxChange', 'handleItem']);

const handleItem = () => {
  emit('handleItem');
};
</script>

<style scoped>
ion-row {
  margin-bottom: .5rem;
  flex-wrap: nowrap;
}
ion-checkbox {
  --size: 2rem;
  --checkmark-width: 5px;
  --checkbox-background: var(--color-background-elevated);
  --checkbox-background-checked: var(--color-success);
  --border-color-checked: transparent;
  margin: 0;
  flex-shrink: 0;
}
ion-checkbox::part(container) {
  padding: 6px;
  border-radius: var(--radius-md);
  border-color: transparent;
}
ion-label {
  flex: 1;
  color: var(--color-text-primary);
  font-size: 1.1rem;
  margin: 4px 0 0 .75rem;
  text-align: left;
  white-space: normal;
  line-height: 1.4;
}
ion-label.disabled {
  opacity: 0.5;
}

/* Small variant */
ion-row.small ion-checkbox {
  --size: 1.25rem;
  --checkmark-width: 3px;
}
ion-row.small ion-checkbox::part(container) {
  padding: 3px;
  border-radius: var(--radius-sm);
}
ion-row.small ion-label {
  font-size: 0.9rem;
  margin-left: 0.5rem;
  margin-top: 0;
}
</style>