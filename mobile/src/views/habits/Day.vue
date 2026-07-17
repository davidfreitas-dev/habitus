<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, IonText, onIonViewWillEnter } from '@ionic/vue';
import { useProfileStore } from '@/stores/profile';
import { useHabitStore } from '@/stores/habits';
import { useParsedDate } from '@/composables/useParsedDate';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';

import Header from '@/components/layout/Header.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Breadcrumb from '@/components/layout/Breadcrumb.vue';
import ProgressBar from '@/components/ui/Progressbar.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';


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
  }, 'Erro ao carregar os dados do dia.');
});

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

        <ProgressBar :progress="progressPercentage" />

        <Checkbox
          v-for="habit in dayInfo.possible_habits"
          :key="habit.id"
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
