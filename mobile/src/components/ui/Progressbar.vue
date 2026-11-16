<script setup lang="ts">
defineProps<{
  progress: string | number;
}>();
</script>

<template>
  <div class="progress-container">
    <div
      :style="{ width: progress + '%' }"
      class="progress"
    />
  </div>
</template>

<style scoped>
div.progress-container {
  position: relative;
  background: var(--color-background-elevated);
  border-radius: var(--radius-lg);
  margin-top: 1rem;
  margin-bottom: 1.25rem;
  height: .75rem;
  width: 100%;
}

div.progress {
  position: absolute;
  top: 0;
  left: 0;
  height: .75rem;
  border-radius: var(--radius-lg);
  background: var(--color-primary);
  transition-property: width;
  transition-duration: 500ms;
}
</style>
