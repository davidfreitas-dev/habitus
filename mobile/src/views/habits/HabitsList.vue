<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  IonRow,
  IonIcon,
  IonText,
  IonList,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  type RefresherCustomEvent,
} from '@ionic/vue';
import { calendarOutline } from 'ionicons/icons';
import { useHabitStore } from '@/stores/habitsStore';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import { useDeferredTour } from '@/composables/useDeferredTour';
import { isHabitDayActive } from '@/utils/habitUtils';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import HabitNewButton from '@/components/features/habits/HabitNewButton.vue';
import HabitListItem from '@/components/features/habits/HabitListItem.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseFilterChips, { type FilterChipItem } from '@/components/base/BaseFilterChips.vue';
import AppConfirmModal from '@/components/app/AppConfirmModal.vue';
import OnboardingTour from '@/components/features/onboarding/OnboardingTour.vue';
import { habitsSteps } from '@/onboarding/habitsSteps';
import { getErrorMessage, type Habit } from '@/types';

const router = useRouter();
const habitStore = useHabitStore();
const { showToast } = useToast();
const { triggerHaptic } = useHaptics();

const searchQuery = ref('');
const selectedWeekDay = ref<number | null>(null);
const isLoadingHabits = ref(false);
const habitToDelete = ref<Habit | null>(null);
const deleteDialogRef = ref<InstanceType<typeof AppConfirmModal> | null>(null);
const ionListRef = ref<InstanceType<typeof IonList> | null>(null);
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

const closeSlidingItems = async () => {
  try {
    await ionListRef.value?.$el?.closeSlidingItems?.();
  } catch {
    // Ignore if not rendered or already closed
  }
};

const weekDayFilters: FilterChipItem<number | null>[] = [
  { label: 'Todos', value: null },
  { label: 'Dom', value: 0 },
  { label: 'Seg', value: 1 },
  { label: 'Ter', value: 2 },
  { label: 'Qua', value: 3 },
  { label: 'Qui', value: 4 },
  { label: 'Sex', value: 5 },
  { label: 'Sáb', value: 6 },
];

const filteredHabits = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return habitStore.habits.filter((habit) => {
    const matchesSearch = !query || habit.title.toLowerCase().includes(query);
    const matchesDay =
      selectedWeekDay.value === null || isHabitDayActive(habit, selectedWeekDay.value);
    return matchesSearch && matchesDay;
  });
});

const loadHabits = async () => {
  if (habitStore.habits.length === 0) {
    isLoadingHabits.value = true;
  }

  try {
    await habitStore.fetchAllHabits();
  } catch (err: unknown) {
    console.error('Erro ao carregar lista de hábitos:', err);
    showToast('error', getErrorMessage(err, 'Erro ao carregar seus hábitos.'));
  } finally {
    isLoadingHabits.value = false;
  }
};

const handleRefresh = async (event: RefresherCustomEvent) => {
  try {
    await habitStore.fetchAllHabits();
  } catch (err: unknown) {
    showToast('error', getErrorMessage(err, 'Erro ao atualizar hábitos.'));
  } finally {
    await event.target.complete();
  }
};

const handleEditHabit = async (habitId: number) => {
  await closeSlidingItems();
  router.push(`/habit/${habitId}`);
};

const promptDeleteHabit = async (habit: Habit) => {
  await closeSlidingItems();
  habitToDelete.value = habit;
  deleteDialogRef.value?.setOpen(true);
};

const confirmDeleteHabit = async () => {
  if (!habitToDelete.value) return;

  try {
    await habitStore.deleteHabit(habitToDelete.value.id);
    await triggerHaptic('medium');
    showToast('success', 'Hábito excluído com sucesso!');
  } catch (err: unknown) {
    await triggerHaptic('error');
    showToast('error', getErrorMessage(err, 'Erro ao excluir hábito.'));
  } finally {
    habitToDelete.value = null;
  }
};

const activeHabitsSteps = computed(() => {
  return habitsSteps.filter((step) => {
    const attachElement = step.attachTo?.element;
    if (attachElement === '#onboarding-habit-card') {
      return filteredHabits.value.length > 0;
    }
    return true;
  });
});

useDeferredTour(onboardingTourRef, () => {
  closeSlidingItems();
  return loadHabits();
});
</script>

<template>
  <ion-page>
    <AppHeader>
      <ion-row class="header-row ion-justify-content-between ion-align-items-center ion-padding-horizontal ion-padding-top">
        <AppHeading title="Meus Hábitos" />
        <HabitNewButton id="onboarding-habits-new" />
      </ion-row>
    </AppHeader>

    <ion-content>
      <ion-refresher slot="fixed" @ion-refresh="handleRefresh">
        <ion-refresher-content />
      </ion-refresher>

      <AppContainer>
        <div class="content">
          <div id="onboarding-habits-search">
            <BaseInput
              v-model="searchQuery"
              type="text"
              placeholder="Buscar hábito..."
            />
          </div>

          <BaseFilterChips
            id="onboarding-habits-chips"
            v-model="selectedWeekDay"
            :items="weekDayFilters"
            aria-label="Filtrar por dia da semana"
          />

          <div v-if="isLoadingHabits" class="habits-list">
            <div
              v-for="n in 4"
              :key="n"
              class="habit-skeleton-card"
            >
              <ion-skeleton-text :animated="true" style="width: 65%; height: 20px; border-radius: 6px;" />
              <ion-skeleton-text :animated="true" style="width: 90%; height: 28px; border-radius: 6px; margin-top: 12px;" />
            </div>
          </div>

          <ion-list
            v-else-if="filteredHabits.length > 0"
            ref="ionListRef"
            lines="none"
            class="habits-list ion-no-padding"
          >
            <HabitListItem
              v-for="(habit, index) in filteredHabits"
              :key="habit.id"
              :habit="habit"
              :card-id="index === 0 ? 'onboarding-habit-card' : undefined"
              @edit="handleEditHabit"
              @delete="promptDeleteHabit"
            />
          </ion-list>

          <div v-else class="empty-state ion-text-center ion-padding">
            <div class="empty-state__icon">
              <ion-icon :icon="calendarOutline" />
            </div>
            <ion-text>
              <p v-if="habitStore.habits.length === 0">
                Você ainda não cadastrou nenhum hábito.
              </p>
              <p v-else>
                Nenhum hábito encontrado para o filtro selecionado.
              </p>
            </ion-text>
            <BaseButton
              v-if="habitStore.habits.length === 0"
              color="primary"
              size="small"
              class="ion-margin-top"
              @click="router.push('/habit')"
            >
              Criar Primeiro Hábito
            </BaseButton>
          </div>
        </div>
      </AppContainer>

      <AppConfirmModal
        ref="deleteDialogRef"
        message="Deseja realmente excluir este hábito?"
        @on-confirm="confirmDeleteHabit"
      />
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="habits"
      :steps="activeHabitsSteps"
    />
  </ion-page>
</template>

<style scoped>
div.content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

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

.habits-list {
  background: transparent;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.habit-skeleton-card {
  width: 100%;
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-xl);
  padding: 16px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2rem;
}

.empty-state__icon {
  font-size: 3rem;
  color: var(--color-text-secondary);
  margin-bottom: 0.5rem;
}
</style>
