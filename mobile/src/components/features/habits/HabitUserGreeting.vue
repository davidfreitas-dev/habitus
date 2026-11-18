<template>
  <router-link to="/tabs/options">
    <ion-row class="ion-justify-content-start ion-align-items-center">
      <ion-avatar class="ion-margin-end avatar-container">
        <ion-icon :icon="person" class="avatar-icon" />
      </ion-avatar>
      <div>
        <h5>Olá, {{ firstName || 'Visitante' }}!</h5>
        <span>{{ currentDate }}</span>
      </div>
    </ion-row>
  </router-link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { IonRow, IonAvatar, IonIcon } from '@ionic/vue';
import { person } from 'ionicons/icons';
import dayjs from '@/lib/dayjs';

const props = withDefaults(
  defineProps<{
    name?: string;
  }>(),
  {
    name: '',
  }
);

const firstName = computed(() => props.name.split(' ')[0]);

const currentDate = dayjs().format('dddd, D MMM');
</script>

<style scoped>
a {
  text-decoration: none;
}

div h5 {
  color: var(--color-text-primary);
  font-weight: 800;
  line-height: 1.75rem;
  max-width: 200px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

div span {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.avatar-container {
  width: 48px;
  height: 48px;
  background: var(--color-background-secondary);
  border: 1px solid var(--color-background-elevated);
  position: relative;
  overflow: hidden;
  display: flex;
  justify-content: center;
  border-radius: 50%;
}

.avatar-icon {
  position: absolute;
  bottom: -3px;
  font-size: 2.625rem;
  color: var(--color-primary);
}
</style>
