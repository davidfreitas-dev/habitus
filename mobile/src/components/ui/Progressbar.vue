<script setup lang="ts">
import { computed } from 'vue';
import { IonProgressBar } from '@ionic/vue';

const props = defineProps<{
  progress: string | number;
}>();

const numericProgress = computed(() => {
  const val = Number(props.progress);
  if (Number.isNaN(val)) return 0;
  return Math.min(100, Math.max(0, val));
});

const normalizedValue = computed(() => numericProgress.value / 100);
</script>

<template>
  <ion-progress-bar
    :value="normalizedValue"
    :style="{ '--progress-width': `${numericProgress}%` }"
    class="habit-progress-bar"
  />
</template>

<style scoped>
ion-progress-bar.habit-progress-bar {
  --background: var(--color-background-elevated);
  --progress-background: var(--color-primary);
  border-radius: var(--radius-lg);
  margin-top: 1rem;
  margin-bottom: 1.25rem;
  height: .75rem;
  width: 100%;
  overflow: hidden;
}
</style>
