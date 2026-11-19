<script setup lang="ts">
import { alertController, type AlertButton, type AlertInput } from '@ionic/vue';

export interface BaseAlertOptions {
  inputs?: AlertInput[];
  buttons?: (string | AlertButton)[];
}

const setOpen = async (
  header: string,
  message: string = '',
  options: BaseAlertOptions = {}
) => {
  const alert = await alertController.create({
    header,
    message,
    cssClass: 'alert-box',
    inputs: options.inputs ?? [],
    buttons: options.buttons ?? ['OK'],
  });

  await alert.present();
};

defineExpose({ setOpen });
</script>

<template>
  <div />
</template>

<style>
ion-alert.alert-box {
  --ion-color-primary: var(--color-success);
}

body.dark ion-alert.alert-box {
  --ion-color-primary: var(--color-success-accent);
}

ion-alert.alert-box .alert-wrapper {
  --background: var(--color-background-secondary);
  --backdrop-opacity: 0.7;
}

ion-alert.alert-box .alert-head h2 {
  color: var(--color-text-primary);
}

ion-alert.alert-box .alert-message {
  color: var(--color-text-accent);
}

ion-alert.alert-box .alert-checkbox-group {
  border-top: 1px solid var(--color-border-default);
  border-bottom: 1px solid var(--color-border-default);
}

ion-alert.alert-box .alert-checkbox-label {
  color: var(--color-text-primary);
}

ion-alert.alert-box .alert-checkbox-icon {
  border-color: var(--color-border-strong);
}

ion-alert.alert-box .alert-tappable[aria-checked='true'] .alert-checkbox-icon {
  border-color: var(--color-success) !important;
  background-color: var(--color-success) !important;
}

ion-alert.alert-box .alert-tappable[aria-checked='true'] .alert-checkbox-inner {
  border-color: #ffffff !important;
}

ion-alert.alert-box .alert-radio-label {
  color: var(--color-text-primary);
}

ion-alert.alert-box .alert-radio-icon {
  border-color: var(--color-border-strong);
}

ion-alert.alert-box .alert-tappable[aria-checked='true'] .alert-radio-icon {
  border-color: var(--color-success) !important;
}

ion-alert.alert-box .alert-tappable[aria-checked='true'] .alert-radio-inner {
  background-color: var(--color-success) !important;
}

ion-alert.alert-box .alert-button {
  color: var(--color-success);
}

body.dark ion-alert.alert-box .alert-button {
  color: var(--color-success-accent);
}

ion-alert.alert-box .alert-button.ion-focused,
ion-alert.alert-box .alert-button:hover,
ion-alert.alert-box .alert-button.ion-activated {
  background: var(--color-background-secondary);
}
</style>
