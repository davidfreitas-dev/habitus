<template>
  <ion-button router-link="/habit">
    <ion-icon :icon="add" />          
    <span>Novo</span>
  </ion-button>
</template>

<script setup lang="ts">
import { IonButton, IonIcon } from '@ionic/vue';
import { add } from 'ionicons/icons';
</script>

<style scoped>
ion-button {
  height: 3rem;
  text-transform: unset;
  
  --background: transparent;
  --background-hover: transparent;
  --background-activated: transparent;
  --background-focused: transparent;
  --border-radius: var(--border-radius-default);
  --border-color: var(--color-primary-accent);
  --border-style: solid;
  --border-width: 1px;
  --box-shadow: none;
}

ion-button ion-icon {
  color: var(--color-primary-accent);
  font-size: 20px;
}

ion-button span {
  color: var(--color-text-primary);
  margin-left: 0.75rem;
  font-weight: 600;
  font-size: 1rem; 
}
</style>
