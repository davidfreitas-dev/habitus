<script setup>
import { ref, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, IonText, onIonViewWillEnter } from '@ionic/vue';
import { useVOnboarding, VOnboardingWrapper } from 'v-onboarding';
import { useProfileStore } from '@/stores/profile';
import { useHabitStore } from '@/stores/habits';
import { useParsedDate } from '@/composables/useParsedDate';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';
import { useOnboarding } from '@/composables/useOnboarding';
import { daySteps } from '@/onboarding/daySteps';

import Header from '@/components/layout/Header.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Breadcrumb from '@/components/layout/Breadcrumb.vue';
import ProgressBar from '@/components/ui/Progressbar.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';
import OnboardingStep from '@/components/onboarding/OnboardingStep.vue';

// Ajuste os caminhos de `onboarding/daySteps` e
// `components/onboarding/OnboardingStep.vue` conforme o local real.


const profileStore = useProfileStore();
const habitStore = useHabitStore();

const dayInfo = ref({
  possible_habits: [],
  completed_habits: []
});

const route = useRoute();
const date = ref(route.params.date);
const {
  parsedDate,
  dayOfWeek,
  dayAndMonth,
  isDateInPast
} = useParsedDate(date.value);

const { showToast } = useToast(); // Initialize useToast

const getDayInfo = async () => {
  const formattedDate = parsedDate.value.format('YYYY-MM-DD');
  try {
    const response = await habitStore.getDayInfo(formattedDate);
    dayInfo.value = response;
  } catch (err) {
    console.error('Error fetching day info:', err);
    showToast('error', err.response?.data?.message || 'Erro ao carregar hábitos do dia.');
    throw err; // Re-throw to propagate to withLoading
  }
};

const { withLoading, isLoading } = useLoading();

onIonViewWillEnter(() => {
  withLoading(async () => {
    await profileStore.fetchProfile();
    await getDayInfo();
    await maybeStartDayOnboarding();
  }, 'Erro ao carregar os dados do dia.');
});

// --- Onboarding: tour da tela do dia (barra de progresso + checklist) ---
// Só mostra o tour para o dia atual (não em datas passadas, que são
// somente leitura), e só quando já existe pelo menos 1 hábito pra marcar.
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref(null);
const { start: startDayOnboarding } = useVOnboarding(onboardingWrapper);

const onDayOnboardingFinish = () => {
  markStepSeen('day');
};

const maybeStartDayOnboarding = async () => {
  if (isDateInPast.value) return;
  if (!dayInfo.value.possible_habits?.length) return;
  if (await isStepSeen('day')) return;
  await nextTick();
  startDayOnboarding();
};

const handleToggleHabit = async (habitId) => {
  await withLoading(async () => {
    const formattedDate = parsedDate.value.format('YYYY-MM-DD');
    await habitStore.toggleHabit(habitId, formattedDate);
    await getDayInfo();
  }, 'Erro ao carregar os dados do dia.');
};

const isHabitChecked = (habitId) => {
  if (!dayInfo.value.completed_habits) {
    return false;
  }
  
  return dayInfo.value.completed_habits.some(habit => String(habit.id) === String(habitId));
};

const progressPercentage = computed(() => {
  const possibleCount = dayInfo.value.possible_habits?.length || 0;
  const completedCount = dayInfo.value.completed_habits?.length || 0;
  return possibleCount > 0
    ? Math.min(100, Math.round((completedCount / possibleCount) * 100))
    : 0;
});

const router = useRouter();
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

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="daySteps"
      @finish="onDayOnboardingFinish"
      @exit="onDayOnboardingFinish"
    >
      <template #default="{ step, isLast, next, exit }">
        <OnboardingStep
          :step="step"
          :index="daySteps.indexOf(step)"
          :is-last="isLast"
          :total="daySteps.length"
          @next="isLast ? exit() : next()"
          @skip="exit()"
        />
      </template>
    </VOnboardingWrapper>
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