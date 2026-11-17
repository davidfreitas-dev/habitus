<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  IonRow,
  IonIcon,
  IonText,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  onIonViewWillEnter,
  type RefresherCustomEvent,
} from '@ionic/vue';
import {
  notificationsOutline,
  createOutline,
  trashOutline,
  calendarOutline,
} from 'ionicons/icons';
import { useHabitStore } from '@/stores/habitsStore';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import ButtonNew from '@/components/habits/ButtonNew.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import ModalDialog from '@/components/layout/ModalDialog.vue';
import { getErrorMessage, type Habit } from '@/types';

const router = useRouter();
const habitStore = useHabitStore();
const { showToast } = useToast();
const { triggerHaptic } = useHaptics();

const searchQuery = ref('');
const selectedWeekDay = ref<number | null>(null);
const isLoadingHabits = ref(false);
const habitToDelete = ref<Habit | null>(null);
const deleteDialogRef = ref<InstanceType<typeof ModalDialog> | null>(null);

const weekDayFilters = [
  { label: 'Todos', value: null },
  { label: 'Dom', value: 0 },
  { label: 'Seg', value: 1 },
  { label: 'Ter', value: 2 },
  { label: 'Qua', value: 3 },
  { label: 'Qui', value: 4 },
  { label: 'Sex', value: 5 },
  { label: 'Sáb', value: 6 },
] as const;

const shortWeekDays = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

const parseWeekDays = (rawDays: number[] | string | undefined): number[] => {
  if (!rawDays) return [];
  if (Array.isArray(rawDays)) return rawDays.map(Number);
  if (typeof rawDays === 'string') {
    return rawDays
      .split(',')
      .filter(Boolean)
      .map(Number);
  }
  return [];
};

const isDayActive = (habit: Habit, dayIndex: number): boolean => {
  return parseWeekDays(habit.week_days).includes(dayIndex);
};

const formatReminderTime = (time: string | null): string | null => {
  if (!time) return null;
  if (time.includes('T')) {
    return time.split('T')[1].substring(0, 5);
  }
  return time.substring(0, 5);
};

const filteredHabits = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return habitStore.habits.filter((habit) => {
    const matchesSearch = !query || habit.title.toLowerCase().includes(query);
    const matchesDay =
      selectedWeekDay.value === null || isDayActive(habit, selectedWeekDay.value);
    return matchesSearch && matchesDay;
  });
});

const selectDayFilter = async (day: number | null) => {
  await triggerHaptic('light');
  selectedWeekDay.value = day;
};

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
  await triggerHaptic('light');
  try {
    await habitStore.fetchAllHabits();
  } catch (err: unknown) {
    showToast('error', getErrorMessage(err, 'Erro ao atualizar hábitos.'));
  } finally {
    await event.target.complete();
  }
};

const handleEditHabit = async (habitId: number) => {
  await triggerHaptic('light');
  router.push(`/habit/${habitId}`);
};

const promptDeleteHabit = async (habit: Habit) => {
  await triggerHaptic('light');
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

onIonViewWillEnter(() => {
  loadHabits();
});
</script>

<template>
  <ion-page>
    <Header>
      <ion-row class="ion-justify-content-between ion-align-items-center ion-padding-horizontal ion-padding-top">
        <Heading title="Meus Hábitos" />
        <ButtonNew />
      </ion-row>
    </Header>

    <ion-content>
      <ion-refresher slot="fixed" @ion-refresh="handleRefresh">
        <ion-refresher-content />
      </ion-refresher>

      <Container>
        <div class="search-input-wrapper">
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Buscar hábito..."
            clear-input
          />
        </div>

        <div
          class="day-filters"
          role="tablist"
          aria-label="Filtrar por dia da semana"
        >
          <button
            v-for="filter in weekDayFilters"
            :key="String(filter.value)"
            type="button"
            class="day-filter-chip"
            :class="{ active: selectedWeekDay === filter.value }"
            @click="selectDayFilter(filter.value)"
          >
            {{ filter.label }}
          </button>
        </div>

        <div v-if="isLoadingHabits" class="habits-list">
          <div
            v-for="n in 4"
            :key="n"
            class="habit-card"
          >
            <ion-skeleton-text :animated="true" style="width: 65%; height: 20px; border-radius: 6px;" />
            <ion-skeleton-text :animated="true" style="width: 90%; height: 28px; border-radius: 6px; margin-top: 12px;" />
          </div>
        </div>

        <div v-else-if="filteredHabits.length > 0" class="habits-list">
          <div
            v-for="habit in filteredHabits"
            :key="habit.id"
            class="habit-card"
            @click="handleEditHabit(habit.id)"
          >
            <div class="habit-card__header">
              <h3 class="habit-card__title">
                {{ habit.title }}
              </h3>
              <div class="habit-card__actions" @click.stop>
                <button
                  type="button"
                  class="icon-btn"
                  aria-label="Editar hábito"
                  @click="handleEditHabit(habit.id)"
                >
                  <ion-icon :icon="createOutline" />
                </button>
                <button
                  type="button"
                  class="icon-btn icon-btn--danger"
                  aria-label="Excluir hábito"
                  @click="promptDeleteHabit(habit)"
                >
                  <ion-icon :icon="trashOutline" />
                </button>
              </div>
            </div>

            <div class="habit-card__footer">
              <div class="week-badges">
                <span
                  v-for="(dayLetter, idx) in shortWeekDays"
                  :key="idx"
                  class="week-badge"
                  :class="{ 'week-badge--active': isDayActive(habit, idx) }"
                >
                  {{ dayLetter }}
                </span>
              </div>

              <div v-if="formatReminderTime(habit.reminder_time)" class="reminder-badge">
                <ion-icon :icon="notificationsOutline" />
                <span>{{ formatReminderTime(habit.reminder_time) }}</span>
              </div>
            </div>
          </div>
        </div>

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
          <Button
            v-if="habitStore.habits.length === 0"
            color="primary"
            size="small"
            class="ion-margin-top"
            @click="router.push('/habit')"
          >
            Criar Primeiro Hábito
          </Button>
        </div>
      </Container>

      <ModalDialog
        ref="deleteDialogRef"
        message="Deseja realmente excluir este hábito?"
        @on-confirm="confirmDeleteHabit"
      />
    </ion-content>
  </ion-page>
</template>

<style scoped>
.search-input-wrapper {
  margin-bottom: 12px;
}

.day-filters {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 8px;
  margin-bottom: 16px;
  scrollbar-width: none;
}

.day-filters::-webkit-scrollbar {
  display: none;
}

.day-filter-chip {
  border: 1px solid var(--color-border-default);
  background: var(--color-background-secondary);
  color: var(--color-text-secondary);
  border-radius: var(--radius-full);
  padding: 6px 14px;
  font-size: 0.8125rem;
  font-weight: 700;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s ease;
}

.day-filter-chip.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-neutral-900);
}

.habits-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.habit-card {
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-xl);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  cursor: pointer;
}

.habit-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.habit-card__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1.35;
}

.habit-card__actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.icon-btn {
  background: var(--color-background-elevated);
  border: none;
  border-radius: var(--radius-md);
  color: var(--color-text-accent);
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
}

.icon-btn--danger {
  color: var(--color-danger);
}

.habit-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.week-badges {
  display: flex;
  gap: 6px;
}

.week-badge {
  width: 26px;
  height: 26px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  background: var(--color-background-elevated);
  color: var(--color-text-secondary);
}

.week-badge--active {
  background: var(--color-primary);
  color: var(--color-neutral-900);
}

.reminder-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--color-background-elevated);
  color: var(--color-text-accent);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
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
