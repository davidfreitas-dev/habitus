<template>
  <ion-icon 
    slot="start"
    :icon="arrowBack" 
    @click="handleBack" 
  />
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { IonIcon } from '@ionic/vue';
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

<style scoped>
ion-icon {
  font-size: 2rem;
  color: var(--color-text-secondary);
  padding: 0 .5rem;
}
</style>
