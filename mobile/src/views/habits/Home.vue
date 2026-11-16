<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { IonPage, IonContent, IonRow, onIonViewWillEnter, onIonViewDidEnter, IonText } from '@ionic/vue';
import { useVOnboarding, VOnboardingStep, VOnboardingWrapper } from 'v-onboarding';
import { useGenerateRange } from '@/composables/useGenerateRange';
import { useProfileStore } from '@/stores/profileStore';
import { useHabitStore } from '@/stores/habitsStore';
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
import type { SummaryDay } from '@/types';

const { generateDatesFromYearBeginning } = useGenerateRange();
const { withLoading } = useLoading();

const profileStore = useProfileStore();
const habitStore = useHabitStore();

const user = computed(() => {
  return profileStore.user;
});

const summary = ref<SummaryDay[]>([]);
const contentRef = ref<any>(null);
const { showToast } = useToast();

const scrollToBottom = async () => {
  await nextTick();
  if (contentRef.value && contentRef.value.$el) {
    await contentRef.value.$el.scrollToBottom(500);
  }
};

// --- Onboarding: tour da tela Início ---
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref<any>(null);
const { start: startHomeOnboarding, finish: finishHomeOnboarding } = useVOnboarding(onboardingWrapper);

const onHomeOnboardingComplete = () => {
  markStepSeen('home');
};

const onHomeOnboardingExit = () => {
  finishHomeOnboarding();
};

const activeHomeSteps = computed(() =>
  summary.value.length
    ? homeSteps
    : homeSteps.filter((step) => (step.attachTo as any)?.element !== '#onboarding-current-day')
);

const maybeStartHomeOnboarding = async () => {
  if (await isStepSeen('home')) return;
  await nextTick();
  startHomeOnboarding();
};

let summaryLoaded: Promise<void> | null = null;

const getSummary = async () => {
  try {
    const today = dayjs().format('YYYY-MM-DD');
    const response = await habitStore.getHabitsSummary(today);
    summary.value = Array.isArray(response) ? response : [];
    scrollToBottom();
  } catch (err: any) {
    console.error('Error fetching habits summary:', err);
    showToast('error', err.response?.data?.message || 'Erro ao carregar resumo de hábitos.');
    throw err;
  }
};

const datesFromYearStart = ref<Date[]>([]);

onIonViewWillEnter(() => {
  datesFromYearStart.value = generateDatesFromYearBeginning();
  summaryLoaded = withLoading(getSummary, 'Erro ao carregar o resumo de hábitos.');
});

onIonViewDidEnter(async () => {
  if (summaryLoaded) {
    try {
      await summaryLoaded;
    } catch (err) {
      // Ignora o erro
    }
  }
  await maybeStartHomeOnboarding();
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
      @finish="onHomeOnboardingComplete"
      @exit="onHomeOnboardingExit"
    >
      <template #default="{ step, isLast, next, exit }">
        <VOnboardingStep>
          <OnboardingStep
            :step="step"
            :index="activeHomeSteps.indexOf(step)"
            :is-last="isLast"
            :total="activeHomeSteps.length"
            @next="isLast ? exit() : next()"
            @skip="exit()"
          />
        </VOnboardingStep>
      </template>
    </VOnboardingWrapper>
  </ion-page>
</template>
