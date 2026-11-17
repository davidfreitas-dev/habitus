<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, IonText, onIonViewWillEnter, onIonViewDidEnter } from '@ionic/vue';
import { useHabitStore } from '@/stores/habitsStore';
import { useParsedDate } from '@/composables/useParsedDate';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import { daySteps } from '@/onboarding/daySteps';
import { getErrorMessage, type DayInfo } from '@/types';

import Header from '@/components/layout/Header.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Breadcrumb from '@/components/layout/Breadcrumb.vue';
import ProgressBar from '@/components/ui/Progressbar.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';
import CongratsModal from '@/components/habits/CongratsModal.vue';
import OnboardingTour from '@/components/onboarding/OnboardingTour.vue';

const habitStore = useHabitStore();
const { triggerHaptic } = useHaptics();
const { showToast } = useToast();
const { withLoading, isLoading } = useLoading();

const route = useRoute();
const router = useRouter();
const date = ref(route.params.date as string);

const {
  parsedDate,
  dayOfWeek,
  dayAndMonth,
  isDateInPast
} = useParsedDate(date.value);

const formattedDate = computed(() => parsedDate.value.format('YYYY-MM-DD'));

const dayInfo = computed<DayInfo>(() => {
  return habitStore.dayCache[formattedDate.value] ?? {
    possible_habits: [],
    completed_habits: [],
  };
});

const getDayInfo = async () => {
  try {
    await habitStore.getDayInfo(formattedDate.value);
  } catch (err: unknown) {
    console.error('Error fetching day info:', err);
    showToast('error', getErrorMessage(err, 'Erro ao carregar hábitos do dia.'));
    throw err;
  }
};

let dayDataLoaded: Promise<void> | null = null;

onIonViewWillEnter(() => {
  const hasCachedDay = Boolean(habitStore.dayCache[formattedDate.value]);
  if (hasCachedDay) {
    dayDataLoaded = getDayInfo();
  } else {
    dayDataLoaded = withLoading(getDayInfo, 'Erro ao carregar os dados do dia.');
  }
});

const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

onIonViewDidEnter(async () => {
  if (dayDataLoaded) {
    try {
      await dayDataLoaded;
    } catch {
      // Ignora erro já exibido
    }
  }
  await onboardingTourRef.value?.startIfNotSeen();
});

const showCongrats = ref(false);
const isToggling = ref<Record<string, boolean>>({});

const progressPercentage = computed(() => {
  const possibleCount = dayInfo.value.possible_habits?.length || 0;
  const completedCount = dayInfo.value.completed_habits?.length || 0;
  return possibleCount > 0
    ? Math.min(100, Math.round((completedCount / possibleCount) * 100))
    : 0;
});

const handleToggleHabit = async (habitId: number | string) => {
  const key = String(habitId);
  if (isToggling.value[key]) return;

  isToggling.value[key] = true;
  const wasCompleted = progressPercentage.value === 100;

  await triggerHaptic('light');

  try {
    await habitStore.toggleHabit(habitId, formattedDate.value);

    if (!wasCompleted && progressPercentage.value === 100) {
      await triggerHaptic('success');
      showCongrats.value = true;
    }
  } catch (err: unknown) {
    await triggerHaptic('error');
    showToast('error', getErrorMessage(err, 'Erro ao atualizar status do hábito.'));
  } finally {
    isToggling.value[key] = false;
  }
};

const isHabitChecked = (habitId: number | string) => {
  if (!dayInfo.value.completed_habits) {
    return false;
  }
  return dayInfo.value.completed_habits.some(habit => String(habit.id) === String(habitId));
};

const canStartTour = computed(() => !isDateInPast.value && Boolean(dayInfo.value.possible_habits?.length));
</script>

<template>
  <ion-page>
    <Header>
      <BackButton />
    </Header>

    <ion-content>
      <Container>
        <Breadcrumb
          :week-day="dayOfWeek"
          :date="dayAndMonth"
        />

        <ProgressBar id="onboarding-progress-bar" :progress="progressPercentage" />

        <Checkbox
          v-for="(habit, index) in dayInfo.possible_habits"
          :key="habit.id"
          :id="index === 0 ? 'onboarding-habit-checkbox' : undefined"
          :label="habit.title"
          :is-checked="isHabitChecked(habit.id)"
          :is-disabled="isDateInPast"
          @handle-item="router.push('/habit/' + habit.id)"
          @handle-checkbox-change="handleToggleHabit(habit.id)"
        />

        <div v-if="!isLoading && !dayInfo.possible_habits.length && !isDateInPast" class="ion-text-center ion-padding empty-habits-container">
          <ion-text>
            <p>Você ainda não criou nenhum hábito.</p>
          </ion-text>
          <Button
            color="primary"
            size="small"
            class="ion-margin-top"
            @click="router.push('/habit')"
          >
            Criar Hábito
          </Button>
        </div>

        <ion-text v-if="!isLoading && isDateInPast" class="ion-text-center ion-padding">
          Você não pode alterar o status de hábitos de datas passadas.
        </ion-text>
      </Container>
    </ion-content>
    
    <CongratsModal v-model:is-open="showCongrats" />

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="day"
      :steps="daySteps"
      :enabled="canStartTour"
    />
  </ion-page>
</template>

<style>
ion-text {
  font-size: .85rem;
  line-height: 1.5;
}

.empty-habits-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}
</style>
