<script setup lang="ts">
import { IonPage, IonContent, IonFooter } from '@ionic/vue';
import AppContainer from '@/components/app/AppContainer.vue';
import AppLogo from '@/components/app/AppLogo.vue';
</script>

<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <AppContainer>
        <div class="auth-layout-body">
          <AppLogo />
          <slot />
        </div>
      </AppContainer>
    </ion-content>

    <ion-footer v-if="$slots.footer" class="ion-no-border ion-padding">
      <div class="auth-footer-prompt">
        <slot name="footer" />
      </div>
    </ion-footer>
  </ion-page>
</template>

<style scoped>
.auth-layout-body {
  display: flex;
  flex-direction: column;
  margin: 1rem 0;
  padding: 0 0.5rem;
}

.auth-footer-prompt {
  text-align: center;
  padding: 1.5rem 1rem;
  font-size: 0.95rem;
  color: var(--color-text-secondary, #666);
  background-color: transparent;
}

.auth-footer-prompt :deep(a) {
  color: var(--color-primary-hover);
  font-weight: 700;
  text-decoration: none;
  margin-left: 0.25rem;
}

.auth-layout-body :deep(.separator) {
  display: flex;
  align-items: center;
  margin: 1rem 0;
  color: var(--color-text-primary);
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
}

.auth-layout-body :deep(.separator::before),
.auth-layout-body :deep(.separator::after) {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-text-primary);
  margin: 0 0.75rem;
}
</style>
