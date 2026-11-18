<script setup lang="ts">
import { useRouter } from 'vue-router';
import { IonButtons, IonButton, IonIcon } from '@ionic/vue';
import { arrowBack } from 'ionicons/icons';

const props = withDefaults(
  defineProps<{
    route?: string;
  }>(),
  {
    route: '',
  }
);

const router = useRouter();

const handleBack = () => {
  if (!props.route) {
    router.go(-1);
    return;
  }

  router.push(props.route);
};
</script>

<template>
  <ion-buttons slot="start">
    <ion-button
      fill="clear"
      aria-label="Voltar"
      class="back-button"
      @click="handleBack"
    >
      <ion-icon slot="icon-only" :icon="arrowBack" />
    </ion-button>
  </ion-buttons>
</template>

<style scoped>
.back-button {
  --color: var(--color-text-secondary);
  --padding-start: 0.5rem;
  --padding-end: 0.5rem;
}

.back-button ion-icon {
  font-size: 2rem;
}
</style>
