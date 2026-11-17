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
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  #container {
    max-width: 900px;
  }
}
</style>
