<script setup lang="ts">
import { IonToggle, type ToggleCustomEvent } from '@ionic/vue';

withDefaults(
  defineProps<{
    checked?: boolean;
  }>(),
  {
    checked: false,
  }
);

const emit = defineEmits<{
  (e: 'update:checked', checked: boolean): void;
}>();

const onToggleChange = (event: ToggleCustomEvent) => {
  emit('update:checked', event.detail.checked);
};
</script>

<template>
  <ion-toggle
    slot="end"
    :checked="checked"
    @ion-change="onToggleChange"
  />
</template>

<style scoped>
ion-toggle {
    --track-background: var(--color-background-elevated);
    --track-background-checked: var(--color-background-elevated);
    --handle-background-checked: var(--color-success);
}
</style>
