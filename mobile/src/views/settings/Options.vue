<script setup>
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { 
  IonContent, 
  IonPage, 
  IonItem, 
  IonLabel, 
  IonList, 
  IonListHeader, 
  IonIcon,
  onIonViewWillEnter
} from '@ionic/vue';
import { personOutline, gridOutline, exitOutline } from 'ionicons/icons';
import { useVOnboarding, VOnboardingStep, VOnboardingWrapper } from 'v-onboarding';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { useLoading } from '@/composables/useLoading';
import { useOnboarding } from '@/composables/useOnboarding';
import { optionsSteps } from '@/onboarding/optionsSteps';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';
import ModalDialog from '@/components/layout/ModalDialog.vue';
import Toggle from '@/components/ui/Toggle.vue';
import OnboardingStep from '@/components/onboarding/OnboardingStep.vue';

// Ajuste os caminhos de `onboarding/optionsSteps` e
// `components/onboarding/OnboardingStep.vue` conforme o local real.

const router = useRouter();
const { withLoading } = useLoading();
const authStore = useAuthStore();
const themeStore = useThemeStore();

const isDarkMode = computed({
  get: () => themeStore.isDarkMode,
  set: (value) => themeStore.setDarkMode(value),
});

const modalRef = ref(null);
    
const handleLogOut = () => {
  modalRef.value?.setOpen(true);
};

const logOut = async () => {
  await withLoading(async () => {
    await authStore.logout();
    router.push('/signin');
  }, 'Erro ao finalizar sessão.');
};

// --- Onboarding: tour da tela de Opções ---
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref(null);
const { start: startOptionsOnboarding, finish: finishOptionsOnboarding } = useVOnboarding(onboardingWrapper);

const onOptionsOnboardingComplete = () => {
  markStepSeen('options');
};

const onOptionsOnboardingExit = () => {
  finishOptionsOnboarding();
};

onIonViewWillEnter(async () => {
  if (await isStepSeen('options')) return;
  await nextTick();
  startOptionsOnboarding();
});
</script>

<template>
  <ion-page ref="pageRef">
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
          >
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Editar perfil
            </ion-label>
          </ion-item>
          <ion-item class="ion-no-padding" router-link="/password-change">
            <ion-label class="ion-no-margin ion-padding-top ion-padding-bottom">
              Alterar senha
            </ion-label>
          </ion-item>
          <ion-item class="ion-no-padding" router-link="/delete-account">
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
          <ion-item class="ion-no-padding" router-link="/about">
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

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="optionsSteps"
      @finish="onOptionsOnboardingComplete"
      @exit="onOptionsOnboardingExit"
    >
      <template #default="{ step, isLast, next, exit }">
        <VOnboardingStep>
          <OnboardingStep
            :step="step"
            :index="optionsSteps.indexOf(step)"
            :is-last="isLast"
            :total="optionsSteps.length"
            @next="isLast ? exit() : next()"
            @skip="exit()"
          />
        </VOnboardingStep>
      </template>
    </VOnboardingWrapper>
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