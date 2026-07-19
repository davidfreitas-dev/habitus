<script setup>
import { ref, computed, nextTick } from 'vue';
import { IonPage, IonContent, IonRow, onIonViewWillEnter, IonText } from '@ionic/vue';
import { useVOnboarding, VOnboardingWrapper } from 'v-onboarding';
import { useGenerateRange } from '@/composables/useGenerateRange';
import { useProfileStore } from '@/stores/profile';
import { useHabitStore } from '@/stores/habits';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';
import { useOnboarding } from '@/composables/useOnboarding';
import { homeSteps } from '@/onboarding/homeSteps';
import Header from '@/components/layout/Header.vue';
import Avatar from '@/components/layout/Avatar.vue';
import ButtonNew from '@/components/habits/ButtonNew.vue';
import WeekDays from '@/components/habits/WeekDays.vue';
import Container from '@/components/layout/Container.vue';
import Summary from '@/components/habits/Summary.vue';
import OnboardingStep from '@/components/onboarding/OnboardingStep.vue';
import dayjs from '@/lib/dayjs';

// Ajuste os caminhos de `onboarding/homeSteps` e
// `components/onboarding/OnboardingStep.vue` conforme o local real.

const { generateDatesFromYearBeginning } = useGenerateRange();
const { withLoading } = useLoading();

const profileStore = useProfileStore();
const habitStore = useHabitStore();

const user = computed(() => {
  return profileStore.user;
});

const summary = ref([]);
const contentRef = ref(null);
const { showToast } = useToast();

const scrollToBottom = async () => {
  await nextTick();
  if (contentRef.value && contentRef.value.$el) {
    await contentRef.value.$el.scrollToBottom(500);
  }
};

// --- Onboarding: tour da tela Início ---
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref(null);
const { start: startHomeOnboarding } = useVOnboarding(onboardingWrapper);

const onHomeOnboardingFinish = () => {
  markStepSeen('home');
};

// homeSteps[0] aponta para #onboarding-summary-grid, que só existe no DOM
// quando summary.length > 0. Para um usuário novo (sem hábitos), esse
// elemento não é renderizado — então removemos esse step da lista nesse caso,
// evitando que o v-onboarding tente ancorar num elemento inexistente.
const activeHomeSteps = computed(() =>
  summary.value.length
    ? homeSteps
    : homeSteps.filter((step) => step.attachTo.element !== '#onboarding-summary-grid')
);

const maybeStartHomeOnboarding = async () => {
  if (await isStepSeen('home')) return;
  // Só mostra o tour se o usuário ainda não tem nenhum hábito hoje.
  // O botão "+ Novo" existe sempre; o grid só existe quando há summary,
  // por isso esperamos o próximo tick antes de iniciar.
  await nextTick();
  startHomeOnboarding();
};

const getSummary = async () => {
  try {
    const today = dayjs().format('YYYY-MM-DD');
    const response = await habitStore.getHabitsSummary(today);
    summary.value = Array.isArray(response) ? response : [];
    scrollToBottom();
    await maybeStartHomeOnboarding();
  } catch (err) {
    console.error('Error fetching habits summary:', err);
    showToast('error', err.response?.data?.message || 'Erro ao carregar resumo de hábitos.');
    throw err;
  }
};

const amountOfDaysToFill = ref(0);
const datesFromYearStart = ref([]);
const minimumSummaryDatesSize = ref(18 * 5);

onIonViewWillEnter(() => {
  datesFromYearStart.value = generateDatesFromYearBeginning();
  amountOfDaysToFill.value = minimumSummaryDatesSize.value - datesFromYearStart.value.length;
  withLoading(getSummary, 'Erro ao carregar o resumo de hábitos.');
});
</script>

<template>
  <ion-page>
    <Header>
      <ion-row class="ion-justify-content-between ion-align-items-center ion-padding">
        <Avatar :name="user?.name || 'Convidado'" />
        <ButtonNew id="onboarding-btn-new" />
      </ion-row>
      <WeekDays />
    </Header>
    <ion-content ref="contentRef">
      <Container class="ion-margin-bottom">
        <Summary
          v-if="summary.length"
          id="onboarding-summary-grid"
          :dates-from-year-start="datesFromYearStart"
          :summary="summary"
        />
        <ion-text
          v-if="!summary.length"
          color="medium"
          class="ion-text-center ion-padding"
        >
          Nenhum dado encontrado.
        </ion-text>
      </Container>
    </ion-content>

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="activeHomeSteps"
      @finish="onHomeOnboardingFinish"
      @exit="onHomeOnboardingFinish"
    >
      <template #default="{ step, isLast, next, exit }">
        <OnboardingStep
          :step="step"
          :index="activeHomeSteps.indexOf(step)"
          :is-last="isLast"
          :total="activeHomeSteps.length"
          @next="isLast ? exit() : next()"
          @skip="exit()"
        />
      </template>
    </VOnboardingWrapper>
  </ion-page>
</template>

<style scoped>
/* Scoped styles */
</style>