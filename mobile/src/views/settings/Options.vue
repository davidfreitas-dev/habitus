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
} from '@ionic/vue';
import { personOutline, gridOutline, exitOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/authStore';
import { useThemeStore } from '@/stores/themeStore';
import { useLoading } from '@/composables/useLoading';
import { useOnboarding } from '@/composables/useOnboarding';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import { analyticsService } from '@/services/analyticsService';
import { optionsSteps } from '@/onboarding/optionsSteps';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';
import ModalDialog from '@/components/layout/ModalDialog.vue';
import Toggle from '@/components/ui/Toggle.vue';
import OnboardingTour from '@/components/onboarding/OnboardingTour.vue';

const router = useRouter();
const { withLoading } = useLoading();
const { resetOnboarding } = useOnboarding();
const { showToast } = useToast();
const { triggerHaptic } = useHaptics();
const authStore = useAuthStore();
const themeStore = useThemeStore();

const isDarkMode = computed({
  get: () => themeStore.isDarkMode,
  set: (value: boolean) => themeStore.setDarkMode(value),
});

const analyticsConsent = ref(true);

const handleAnalyticsConsentChange = async (enabled: boolean) => {
  analyticsConsent.value = enabled;
  await triggerHaptic('light');
  await analyticsService.setConsent(enabled);
};

const modalRef = ref<InstanceType<typeof ModalDialog> | null>(null);
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

const handleReplayTutorial = async () => {
  await triggerHaptic('light');
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
    router.push('/signin');
  }, 'Erro ao finalizar sessão.');
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
    <Header>
      <Heading title="Opções" class="ion-padding-horizontal" />
    </Header>
    <ion-content>
      <Container>
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
            <Toggle v-model:checked="isDarkMode" />
          </ion-item>
          <ion-item class="ion-no-padding">
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Telemetria de uso (LGPD)
            </ion-label>
            <Toggle
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

        <Button
          color="primary"
          class="ion-margin-top"
          @click="handleLogOut"
        >
          <ion-icon slot="start" :icon="exitOutline" />
          Finalizar Sessão
        </Button>

        <ModalDialog
          ref="modalRef"
          message="Deseja realmente finalizar a sessão?"
          @on-confirm="logOut"
        />
      </Container>
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
  margin-top: 1rem;
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
