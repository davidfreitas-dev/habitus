<script setup lang="ts">
import logoImg from '../../../assets/logo.png';

withDefaults(
  defineProps<{
    to?: string;
    alt?: string;
  }>(),
  {
    to: '',
    alt: 'Habitus',
  }
);
</script>

<template>
  <div class="logo-wrapper">
    <router-link v-if="to" :to="to">
      <img
        :src="logoImg"
        :alt="alt"
        class="logo"
      >
    </router-link>
    <img
      v-else
      :src="logoImg"
      :alt="alt"
      class="logo"
    >
  </div>
</template>

<style scoped>
.logo-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: calc(env(safe-area-inset-top, 0px) + 1rem);
}

.logo {
  width: auto;
  height: 70px;
  object-fit: contain;
}
</style>
