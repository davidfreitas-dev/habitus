<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import {
  IonPage,
  IonContent,
  IonRow,
  IonText,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  onIonViewWillEnter,
  onIonViewDidEnter,
  type RefresherCustomEvent,
} from '@ionic/vue';
import { useGenerateRange } from '@/composables/useGenerateRange';
import { useProfileStore } from '@/stores/profileStore';
import { useHabitStore } from '@/stores/habitsStore';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import { homeSteps } from '@/onboarding/homeSteps';
import Header from '@/components/layout/Header.vue';
import Avatar from '@/components/layout/Avatar.vue';
import ButtonNew from '@/components/habits/ButtonNew.vue';
import WeekDays from '@/components/habits/WeekDays.vue';
import Container from '@/components/layout/Container.vue';
import Summary from '@/components/habits/Summary.vue';
import OnboardingTour from '@/components/onboarding/OnboardingTour.vue';
import dayjs from '@/lib/dayjs';
import { getErrorMessage } from '@/types';

const { generateDatesFromYearBeginning } = useGenerateRange();
const { triggerHaptic } = useHaptics();
const { showToast } = useToast();

const profileStore = useProfileStore();
const habitStore = useHabitStore();

const user = computed(() => profileStore.user);
const summary = computed(() => habitStore.summary);

interface IonContentElement {
  $el?: {
    scrollToBottom: (duration?: number) => Promise<void>;
  };
}

const contentRef = ref<IonContentElement | null>(null);
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);
const isLoadingSummary = ref(false);

const scrollToBottom = async () => {
  await nextTick();
  if (contentRef.value?.$el) {
    await contentRef.value.$el.scrollToBottom(500);
  }
};

const activeHomeSteps = computed(() =>
  summary.value.length
    ? homeSteps
    : homeSteps.filter((step) => {
      const attachElement = (step.attachTo as { element?: string } | undefined)?.element;
      return attachElement !== '#onboarding-current-day';
    })
);

let summaryLoaded: Promise<void> | null = null;

const getSummary = async (shouldScroll = true) => {
  const showSkeleton = summary.value.length === 0;
  if (showSkeleton) {
    isLoadingSummary.value = true;
  }

  try {
    const today = dayjs().format('YYYY-MM-DD');
    await habitStore.getHabitsSummary(today);
    if (shouldScroll) {
      await scrollToBottom();
    }
  } catch (err: unknown) {
    console.error('Error fetching habits summary:', err);
    showToast('error', getErrorMessage(err, 'Erro ao carregar resumo de hábitos.'));
    throw err;
  } finally {
    isLoadingSummary.value = false;
  }
};

const handleRefresh = async (event: RefresherCustomEvent) => {
  await triggerHaptic('light');
  try {
    await getSummary(false);
  } catch {
    // Erro tratado no getSummary
  } finally {
    await event.target.complete();
  }
};

const datesFromYearStart = ref<Date[]>([]);

onIonViewWillEnter(() => {
  datesFromYearStart.value = generateDatesFromYearBeginning();
  summaryLoaded = getSummary(summary.value.length === 0);
});

onIonViewDidEnter(async () => {
  if (summaryLoaded) {
    try {
      await summaryLoaded;
    } catch {
      // Ignora o erro
    }
  }
  await onboardingTourRef.value?.startIfNotSeen();
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
      <ion-refresher slot="fixed" @ion-refresh="handleRefresh">
        <ion-refresher-content />
      </ion-refresher>

      <Container class="ion-margin-bottom">
        <div v-if="isLoadingSummary" class="skeleton-grid">
          <ion-skeleton-text
            v-for="n in 35"
            :key="n"
            :animated="true"
            class="skeleton-day"
          />
        </div>

        <Summary
          v-else-if="datesFromYearStart.length"
          id="onboarding-summary-grid"
          :dates-from-year-start="datesFromYearStart"
          :summary="summary"
        />

        <ion-text
          v-else
          color="medium"
          class="ion-text-center ion-padding"
        >
          Nenhum dado encontrado.
        </ion-text>
      </Container>
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="home"
      :steps="activeHomeSteps"
    />
  </ion-page>
</template>

<style scoped>
.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  justify-items: center;
  padding-top: 8px;
}

.skeleton-day {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}
</style>
