<template>
  <h1>{{ title }}</h1>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string;
  }>(),
  {
    title: 'Heading',
  }
);
</script>

<style scoped>
h1 {
  color: var(--color-text-primary);
  font-weight: 800;
  font-size: 1.75rem;
  margin: 0;
}
</style>
