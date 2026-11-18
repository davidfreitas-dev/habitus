<script setup lang="ts">
import { alertController } from '@ionic/vue';

const setOpen = async (header: string, message: string) => {
  const alert = await alertController.create({
    header: header,
    message: message,
    cssClass: 'alert-box',
    buttons: ['OK'],
  });

  await alert.present();
};

defineExpose({ setOpen });
</script>

<template>
  <div />
</template>

<style>
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

ion-alert.alert-box .alert-button {
  color: var(--color-primary);
}

ion-alert.alert-box .alert-button.ion-focused,
ion-alert.alert-box .alert-button:hover,
ion-alert.alert-box .alert-button.ion-activated {
  background: var(--color-background-secondary);
}
</style>
