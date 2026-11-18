<script setup lang="ts">
import { ref, computed, nextTick, watch } from 'vue';
import { useRoute } from 'vue-router';
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
import { useDayDimensions } from '@/composables/useDayDimensions';
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
import DaySheetModal from '@/components/habits/DaySheetModal.vue';
import OnboardingTour from '@/components/onboarding/OnboardingTour.vue';
import dayjs from '@/lib/dayjs';
import { getErrorMessage } from '@/types';

const { generateDatesFromYearBeginning } = useGenerateRange();
const { isHorizontalLayout } = useDayDimensions();
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
  scrollToBottom?: (duration?: number) => Promise<void>;
}

const contentRef = ref<IonContentElement | null>(null);
const summaryScrollRef = ref<HTMLElement | null>(null);
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);
const isLoadingSummary = ref(false);
const isDaySheetOpen = ref(false);
const selectedDate = ref<Date | null>(null);

const route = useRoute();

watch(
  () => [route.query.action, route.query.date],
  ([action, queryDate]) => {
    if (action === 'today') {
      selectedDate.value = new Date();
      isDaySheetOpen.value = true;
    } else if (queryDate && typeof queryDate === 'string') {
      const parsed = dayjs(queryDate).toDate();
      if (!isNaN(parsed.getTime())) {
        selectedDate.value = parsed;
        isDaySheetOpen.value = true;
      }
    }
  },
  { immediate: true }
);

const handleSelectDay = async (date: Date) => {
  await triggerHaptic('light');
  selectedDate.value = date;
  isDaySheetOpen.value = true;
};

const skeletonCount = computed(() => (isHorizontalLayout.value ? 126 : 35));

const getContentElement = () => {
  if (!contentRef.value) return null;
  if ('$el' in contentRef.value && contentRef.value.$el) {
    return contentRef.value.$el;
  }
  return contentRef.value;
};

const scrollToCurrentDay = async (duration = 500) => {
  await nextTick();
  await new Promise((resolve) => requestAnimationFrame(resolve));

  if (isHorizontalLayout.value) {
    if (summaryScrollRef.value) {
      summaryScrollRef.value.scrollTo({
        left: summaryScrollRef.value.scrollWidth,
        behavior: 'smooth',
      });
    }
    return;
  }

  const contentEl = getContentElement();
  if (contentEl && typeof contentEl.scrollToBottom === 'function') {
    await contentEl.scrollToBottom(duration);
  }
};

watch(isHorizontalLayout, async () => {
  await scrollToCurrentDay();
});

const activeHomeSteps = computed(() =>
  summary.value.length
    ? homeSteps
    : homeSteps.filter((step) => {
      const attachElement = (step.attachTo as { element?: string } | undefined)?.element;
      return attachElement !== '#onboarding-current-day';
    })
);

let summaryLoaded: Promise<void> | null = null;

const getSummary = async (shouldScroll = false) => {
  const showSkeleton = summary.value.length === 0;
  if (showSkeleton) {
    isLoadingSummary.value = true;
  }

  try {
    const today = dayjs().format('YYYY-MM-DD');
    await habitStore.getHabitsSummary(today);
    isLoadingSummary.value = false;
    if (shouldScroll) {
      await scrollToCurrentDay();
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
  summaryLoaded = getSummary(false);
});

onIonViewDidEnter(async () => {
  if (datesFromYearStart.value.length > 0) {
    await scrollToCurrentDay();
  }

  if (summaryLoaded) {
    try {
      await summaryLoaded;
      await scrollToCurrentDay();
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
      <ion-row class="header-row ion-justify-content-between ion-align-items-center ion-padding">
        <Avatar :name="user?.name || 'Convidado'" />
        <ButtonNew id="onboarding-btn-new" />
      </ion-row>
      <WeekDays v-if="!isHorizontalLayout" />
    </Header>
    <ion-content ref="contentRef">
      <ion-refresher slot="fixed" @ion-refresh="handleRefresh">
        <ion-refresher-content />
      </ion-refresher>

      <Container class="ion-margin-bottom">
        <div
          v-if="isLoadingSummary || datesFromYearStart.length"
          :class="['summary-wrapper', { 'summary-wrapper--horizontal': isHorizontalLayout }]"
        >
          <WeekDays v-if="isHorizontalLayout" direction="vertical" />

          <div
            ref="summaryScrollRef"
            :class="['summary-scroll-container', { 'summary-scroll-container--horizontal': isHorizontalLayout }]"
          >
            <div
              v-if="isLoadingSummary"
              :class="['skeleton-grid', { 'skeleton-grid--horizontal': isHorizontalLayout }]"
            >
              <ion-skeleton-text
                v-for="n in skeletonCount"
                :key="n"
                :animated="true"
                class="skeleton-day"
              />
            </div>

            <Summary
              v-else
              id="onboarding-summary-grid"
              :dates-from-year-start="datesFromYearStart"
              :summary="summary"
              @select-day="handleSelectDay"
            />
          </div>
        </div>

        <ion-text
          v-else
          color="medium"
          class="ion-text-center ion-padding"
        >
          Nenhum dado encontrado.
        </ion-text>
      </Container>
    </ion-content>

    <DaySheetModal
      v-model:is-open="isDaySheetOpen"
      :date="selectedDate"
    />

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="home"
      :steps="activeHomeSteps"
    />
  </ion-page>
</template>

<style scoped>
.header-row {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .header-row {
    max-width: 900px;
  }
}

.summary-wrapper {
  width: 100%;
}

.summary-wrapper--horizontal {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
}

.summary-scroll-container {
  width: 100%;
}

.summary-scroll-container--horizontal {
  flex: 1;
  min-width: 0;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  justify-items: center;
  padding-top: 8px;
}

.skeleton-grid--horizontal {
  grid-template-columns: unset;
  grid-template-rows: repeat(7, auto);
  grid-auto-flow: column;
  width: max-content;
  padding-top: 0;
}

.skeleton-day {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  margin: 0;
}
</style>
