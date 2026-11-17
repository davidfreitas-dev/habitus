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
  IonItemSliding,
  IonItem,
  IonItemOptions,
  IonItemOption,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  onIonViewWillEnter,
  type RefresherCustomEvent,
} from '@ionic/vue';
import {
  notifications,
  notificationsOff,
  trashOutline,
  createOutline,
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
import FilterChips, { type FilterChipItem } from '@/components/ui/FilterChips.vue';
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
const ionListRef = ref<InstanceType<typeof IonList> | null>(null);

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

const getFrequencySummary = (habit: Habit): string => {
  const days = Array.from(new Set(parseWeekDays(habit.week_days))).sort((a, b) => a - b);
  const count = days.length;

  if (count === 7) return 'Todos os dias';
  if (count === 5 && [1, 2, 3, 4, 5].every((d) => days.includes(d))) {
    return '5x na semana • Dias úteis';
  }
  if (count === 2 && [0, 6].every((d) => days.includes(d))) {
    return '2x na semana • Fim de semana';
  }
  if (count === 1) return '1x na semana';
  return `${count}x na semana`;
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
  await closeSlidingItems();
  await triggerHaptic('light');
  router.push(`/habit/${habitId}`);
};

const promptDeleteHabit = async (habit: Habit) => {
  await closeSlidingItems();
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
  closeSlidingItems();
  loadHabits();
});
</script>

<template>
  <ion-page>
    <Header>
      <ion-row class="header-row ion-justify-content-between ion-align-items-center ion-padding-horizontal ion-padding-top">
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
            type="text"
            placeholder="Buscar hábito..."
          />
        </div>

        <FilterChips
          v-model="selectedWeekDay"
          :items="weekDayFilters"
          aria-label="Filtrar por dia da semana"
        />

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

        <ion-list
          v-else-if="filteredHabits.length > 0"
          ref="ionListRef"
          lines="none"
          class="habits-list ion-no-padding"
        >
          <ion-item-sliding
            v-for="habit in filteredHabits"
            :key="habit.id"
            class="habit-sliding-item"
          >
            <ion-item-options side="start" class="sliding-options-start">
              <ion-item-option
                class="sliding-option-btn sliding-option-edit"
                aria-label="Editar hábito"
                @click="handleEditHabit(habit.id)"
              >
                <ion-icon slot="top" :icon="createOutline" />
                <span class="sliding-option-label">Editar</span>
              </ion-item-option>
            </ion-item-options>

            <ion-item class="habit-card-item ion-no-padding" lines="none">
              <div
                class="habit-card"
                @click="handleEditHabit(habit.id)"
              >
                <div class="habit-card__header">
                  <div class="habit-card__info">
                    <h3 class="habit-card__title">
                      {{ habit.title }}
                    </h3>
                    <span class="habit-card__subtitle">
                      {{ getFrequencySummary(habit) }}
                    </span>
                  </div>

                  <div class="habit-card__actions" @click.stop>
                    <div
                      class="reminder-badge"
                      :class="{ 'reminder-badge--off': !formatReminderTime(habit.reminder_time) }"
                    >
                      <ion-icon
                        :icon="formatReminderTime(habit.reminder_time) ? notifications : notificationsOff"
                        class="reminder-badge__icon"
                      />
                      <span v-if="formatReminderTime(habit.reminder_time)">
                        {{ formatReminderTime(habit.reminder_time) }}
                      </span>
                    </div>
                  </div>
                </div>

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
              </div>
            </ion-item>

            <ion-item-options side="end" class="sliding-options-end">
              <ion-item-option
                class="sliding-option-btn sliding-option-delete"
                aria-label="Excluir hábito"
                @click="promptDeleteHabit(habit)"
              >
                <ion-icon slot="top" :icon="trashOutline" />
                <span class="sliding-option-label">Excluir</span>
              </ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
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

.search-input-wrapper {
  margin-bottom: 12px;
}

.habits-list {
  background: transparent;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.habit-sliding-item {
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: transparent;
}

.habit-card-item {
  --background: transparent;
  --inner-padding-end: 0;
  --padding-start: 0;
  width: 100%;
}

.sliding-options-start {
  border-top-left-radius: var(--radius-xl);
  border-bottom-left-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--color-background-elevated);
}

.sliding-options-end {
  border-top-right-radius: var(--radius-xl);
  border-bottom-right-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--color-background-elevated);
}

.sliding-option-btn {
  padding: 0 20px;
  min-width: 76px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.sliding-option-btn ion-icon {
  font-size: 1.45rem;
  margin-bottom: 2px;
}

.sliding-option-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: capitalize;
}

.sliding-option-edit {
  --background: var(--color-background-elevated);
  --color: var(--color-text-primary);
  color: var(--color-text-primary);
}

.sliding-option-edit ion-icon,
.sliding-option-edit .sliding-option-label {
  color: var(--color-text-primary);
}

.sliding-option-edit:active {
  --background: var(--color-background-primary);
}

.sliding-option-delete {
  --background: var(--color-background-elevated);
  --color: var(--color-danger);
  color: var(--color-danger);
}

.sliding-option-delete ion-icon,
.sliding-option-delete .sliding-option-label {
  color: var(--color-danger);
}

.sliding-option-delete:active {
  --background: var(--color-background-primary);
}

.habit-card {
  width: 100%;
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-xl);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  cursor: pointer;
  transition: transform 0.15s ease, border-color 0.15s ease;
}

.habit-card:active {
  border-color: var(--color-border-strong);
}

.habit-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.habit-card__info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.habit-card__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1.3;
}

.habit-card__subtitle {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.habit-card__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.week-badges {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}

.week-badge {
  height: 30px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  background: var(--habit-bg-0);
  border: 1.5px solid var(--habit-border-0);
  color: var(--color-text-secondary);
}

.week-badge--active {
  background: var(--color-primary);
  border-color: var(--habit-border-100);
  color: var(--color-neutral-900);
}

.reminder-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  background: var(--color-background-elevated);
  border: 1px solid var(--color-border-default);
  color: var(--color-text-accent);
  height: 32px;
  padding: 0 10px;
  border-radius: var(--radius-md);
  font-size: 0.8125rem;
  font-weight: 700;
  line-height: 1;
}

.reminder-badge--off {
  width: 32px;
  padding: 0;
  color: var(--color-text-secondary);
}

.reminder-badge__icon {
  font-size: 1.05rem;
  flex-shrink: 0;
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
