<template>
  <ion-modal
    :is-open="isOpen"
    :can-dismiss="canDismiss"
    :presenting-element="presentingElement"
  >
    <ion-header class="ion-no-border">
      <ion-toolbar class="ion-padding-top">
        <ion-icon 
          slot="start"
          :icon="arrowBack" 
          @click="setOpen(false)" 
        />
        <ion-title v-if="title">
          {{ title }}
        </ion-title> 
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <div id="container">
        <slot /> 
      </div>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonModal, IonHeader, IonToolbar, IonContent, IonTitle, IonIcon } from '@ionic/vue';
import { arrowBack } from 'ionicons/icons';

withDefaults(
  defineProps<{
    presentingElement?: HTMLElement;
    title?: string;
  }>(),
  {
    presentingElement: undefined,
    title: '',
  }
);

const canDismiss = (_data?: unknown, role?: string) => {
  return role !== 'gesture' && role !== 'backdrop';
};

const isOpen = ref(false);

const setOpen = (status: boolean) => {
  isOpen.value = status;
};

defineExpose({ setOpen });
</script>

<style scoped>
ion-title {
  color: var(--color-text-primary);
  font-weight: 800;
  font-size: 1.875rem;
  padding-left: 0; 
}
h1 {
  color: var(--color-text-primary);
  font-weight: 800;
  font-size: 1.875rem;
  margin: 0;
}
ion-icon {
  font-size: 2rem;
  color: var(--color-text-secondary);
  padding: 0 1rem;
}
</style>
