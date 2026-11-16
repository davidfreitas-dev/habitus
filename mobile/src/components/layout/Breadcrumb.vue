<template>
  <div>
    {{ weekDay }}

    <span>
      {{ date }}
    </span>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  weekDay: string;
  date: string;
}>();
</script>

<style scoped>
div {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;

  color: var(--color-text-secondary);
  font-weight: 600;
  font-size: 1rem;
  text-transform: lowercase; 
}

div span{
  color: var(--color-text-primary);
  font-size: 1.875rem;
  line-height: 2.25rem;
  font-weight: 800;
}
</style>
