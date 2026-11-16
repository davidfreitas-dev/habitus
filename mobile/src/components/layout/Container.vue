<template>
  <div id="container">
    <slot />
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
#container {
  display: flex;
  flex-direction: column;
  padding: 1rem;
}
</style>
