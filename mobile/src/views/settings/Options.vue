<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonContent,
  IonPage,
  IonItem,
  IonLabel,
  IonList,
  IonListHeader,
  IonIcon,
  onIonViewWillEnter,
  onIonViewDidEnter,
  useIonRouter,
} from '@ionic/vue';
import { personOutline, gridOutline, exitOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/authStore';
import { useThemeStore } from '@/stores/themeStore';
import { useLoading } from '@/composables/useLoading';
import { useOnboarding } from '@/composables/useOnboarding';
import { useToast } from '@/composables/useToast';
import { analyticsService } from '@/services/analyticsService';
import { optionsSteps } from '@/onboarding/optionsSteps';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import AppConfirmModal from '@/components/app/AppConfirmModal.vue';
import BaseToggle from '@/components/base/BaseToggle.vue';
import OnboardingTour from '@/components/features/onboarding/OnboardingTour.vue';

const router = useRouter();
const ionRouter = useIonRouter();
const { withLoading } = useLoading();
const { resetOnboarding } = useOnboarding();
const { showToast } = useToast();
const authStore = useAuthStore();
const themeStore = useThemeStore();

const isDarkMode = computed({
  get: () => themeStore.isDarkMode,
  set: (value: boolean) => themeStore.setDarkMode(value),
});

const analyticsConsent = ref(true);

const handleAnalyticsConsentChange = async (enabled: boolean) => {
  analyticsConsent.value = enabled;
  await analyticsService.setConsent(enabled);
};

const modalRef = ref<InstanceType<typeof AppConfirmModal> | null>(null);
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

const handleReplayTutorial = async () => {
  await resetOnboarding(['welcome', 'home', 'day', 'form', 'stats', 'options', 'habits']);
  void analyticsService.logEvent({ name: 'onboarding_reset' });
  showToast('success', 'Tutorial reiniciado!');
  router.push('/onboarding/welcome');
};

const handleLogOut = () => {
  modalRef.value?.setOpen(true);
};

const logOut = async () => {
  await withLoading(async () => {
    await authStore.logout();
    ionRouter.navigate('/signin', 'root', 'replace');
  }, 'Erro ao encerrar sessão.');
};

onIonViewWillEnter(async () => {
  analyticsConsent.value = await analyticsService.getConsent();
});

onIonViewDidEnter(async () => {
  await onboardingTourRef.value?.startIfNotSeen();
});
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppHeading title="Opções" class="ion-padding-horizontal ion-padding-top" />
    </AppHeader>
    <ion-content>
      <AppContainer>
        <ion-list lines="none" class="ion-no-padding">
          <ion-list-header class="ion-no-padding">
            <ion-label class="ion-no-margin ion-padding-bottom ion-padding-top">
              <ion-icon :icon="personOutline" />
              Minha conta
            </ion-label>
          </ion-list-header>
          <ion-item
            id="onboarding-profile-link"
            class="ion-no-padding"
            router-link="/profile"
            :detail="true"
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Editar perfil
            </ion-label>
          </ion-item>
          <ion-item
            class="ion-no-padding"
            router-link="/password-change"
            :detail="true"
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Alterar senha
            </ion-label>
          </ion-item>
          <ion-item
            class="ion-no-padding"
            router-link="/delete-account"
            :detail="true"
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Excluir conta
            </ion-label>
          </ion-item>
        </ion-list>

        <br>

        <ion-list lines="none" class="ion-no-padding">
          <ion-list-header class="ion-no-padding">
            <ion-label class="ion-no-margin ion-padding-bottom ion-padding-top">
              <ion-icon :icon="gridOutline" />
              Mais
            </ion-label>
          </ion-list-header>
          <ion-item class="ion-no-padding">
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Modo escuro
            </ion-label>
            <BaseToggle v-model:checked="isDarkMode" />
          </ion-item>
          <ion-item class="ion-no-padding">
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Telemetria de uso (LGPD)
            </ion-label>
            <BaseToggle
              :checked="analyticsConsent"
              @update:checked="handleAnalyticsConsentChange"
            />
          </ion-item>
          <ion-item
            class="ion-no-padding"
            button
            :detail="true"
            @click="handleReplayTutorial"
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Rever tutorial do app
            </ion-label>
          </ion-item>
          <ion-item
            class="ion-no-padding"
            router-link="/about"
            :detail="true"
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Sobre o app
            </ion-label>
          </ion-item>
        </ion-list>

        <br>

        <BaseButton
          color="primary"
          class="ion-margin-top"
          @click="handleLogOut"
        >
          <ion-icon slot="start" :icon="exitOutline" />
          Encerrar Sessão
        </BaseButton>

        <AppConfirmModal
          ref="modalRef"
          message="Deseja realmente encerrar a sessão?"
          @on-confirm="logOut"
        />
      </AppContainer>
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="options"
      :steps="optionsSteps"
    />
  </ion-page>
</template>

<style scoped>
ion-list {
  background: var(--color-background-primary);
}
ion-list-header {
  color: var(--color-text-primary);
  font-size: 1.1rem;
  margin-bottom: .5rem;
  border-bottom: 1px solid var(--color-background-elevated);
}
ion-list-header ion-icon {
  font-size: 1.2rem;
  margin-right: .5rem;
  --ionicon-stroke-width: 40px;
}
ion-item {
  color: var(--color-text-accent);
  font-size: 1.1rem;
  --inner-padding-end: 0;
}
ion-item ion-icon {
  font-size: 1.2rem;
  color: var(--color-text-accent);
}
ion-label ion-icon {
  margin-bottom: -2px;
}
</style>
