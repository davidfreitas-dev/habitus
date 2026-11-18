<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { IonTabBar, IonTabButton, IonTabs, IonLabel, IonIcon, IonPage, IonRouterOutlet } from '@ionic/vue';
import { homeOutline, home, listOutline, list, statsChartOutline, statsChart, cogOutline, cog } from 'ionicons/icons';
import { useAuthStore } from '@/stores/authStore';

const route = useRoute();
const authStore = useAuthStore();

const activeTab = computed(() => {
  const path = route.path;
  if (path.endsWith('/habits')) return 'habits';
  if (path.endsWith('/statistics')) return 'statistics';
  if (path.endsWith('/options')) return 'options';
  return 'home';
});
</script>

<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet :key="authStore.sessionId" />
      <ion-tab-bar slot="bottom" class="ion-safe-area-bottom">
        <ion-tab-button tab="home" href="/tabs/home">
          <ion-icon :icon="activeTab === 'home' ? home : homeOutline" />
          <ion-label>Início</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="habits" href="/tabs/habits">
          <ion-icon :icon="activeTab === 'habits' ? list : listOutline" />
          <ion-label>Hábitos</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="statistics" href="/tabs/statistics">
          <ion-icon :icon="activeTab === 'statistics' ? statsChart : statsChartOutline" />
          <ion-label>Estatísticas</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="options" href="/tabs/options">
          <ion-icon :icon="activeTab === 'options' ? cog : cogOutline" />
          <ion-label>Opções</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<style scoped>
ion-tab-bar {
  --background: var(--color-background-primary);
  --border: 1px solid var(--color-background-elevated);
}
ion-tab-button {
  --color-focused: var(--color-primary);
  --color-selected: var(--color-primary);
}
ion-icon {
  font-size: 24px;
}
ion-label {     
  font-size: 12px;
  font-weight: 600;
}
</style>
