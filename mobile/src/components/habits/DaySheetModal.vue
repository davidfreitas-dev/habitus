<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonModal,
  IonContent,
  IonButton,
  IonIcon,
  IonText,
  IonSpinner,
} from '@ionic/vue';
import { closeOutline, addOutline } from 'ionicons/icons';
import { useHabitStore } from '@/stores/habitsStore';
import { useParsedDate } from '@/composables/useParsedDate';
import { useHaptics } from '@/composables/useHaptics';
import { useCelebrationSound } from '@/composables/useCelebrationSound';
import { useToast } from '@/composables/useToast';
import { getErrorMessage, type DayInfo } from '@/types';
import confetti from 'canvas-confetti';
import Breadcrumb from '@/components/layout/Breadcrumb.vue';
import ProgressBar from '@/components/ui/Progressbar.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';
import OnboardingTour from '@/components/onboarding/OnboardingTour.vue';
import { daySteps } from '@/onboarding/daySteps';

const props = withDefaults(
  defineProps<{
    isOpen: boolean;
    date?: Date | string | null;
  }>(),
  {
    isOpen: false,
    date: null,
  }
);

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void;
}>();

const router = useRouter();
const habitStore = useHabitStore();
const { triggerHaptic } = useHaptics();
const { playCelebrationSound } = useCelebrationSound();
const { showToast } = useToast();

const {
  parsedDate,
  dayOfWeek,
  dayAndMonth,
  isDateEditable,
} = useParsedDate(() => props.date || new Date());

const formattedDate = computed(() => parsedDate.value.format('YYYY-MM-DD'));

const isLoading = ref(false);
const isToggling = ref<Record<string, boolean>>({});
const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

const dayInfo = computed<DayInfo>(() => {
  return habitStore.dayCache[formattedDate.value] ?? {
    possible_habits: [],
    completed_habits: [],
  };
});

const canStartTour = computed(() => isDateEditable.value && Boolean(dayInfo.value.possible_habits?.length));

const progressPercentage = computed(() => {
  const possibleCount = dayInfo.value.possible_habits?.length || 0;
  const completedCount = dayInfo.value.completed_habits?.length || 0;
  return possibleCount > 0
    ? Math.min(100, Math.round((completedCount / possibleCount) * 100))
    : 0;
});

const startTourIfPossible = async () => {
  if (props.isOpen && canStartTour.value) {
    await nextTick();
    await onboardingTourRef.value?.startIfNotSeen();
  }
};

const fetchDayData = async () => {
  if (!props.isOpen || !props.date) return;
  isLoading.value = true;
  try {
    await habitStore.getDayInfo(formattedDate.value);
    await startTourIfPossible();
  } catch (err: unknown) {
    showToast('error', getErrorMessage(err, 'Erro ao carregar hábitos do dia.'));
  } finally {
    isLoading.value = false;
  }
};

watch(
  () => [props.isOpen, props.date],
  ([isOpen]) => {
    if (isOpen) {
      void fetchDayData();
    }
  },
  { immediate: true }
);

const closeModal = () => {
  onboardingTourRef.value?.finish();
  emit('update:isOpen', false);
};

const isHabitChecked = (habitId: number | string) => {
  if (!dayInfo.value.completed_habits) return false;
  return dayInfo.value.completed_habits.some(h => String(h.id) === String(habitId));
};

const fireConfetti = () => {
  playCelebrationSound();
  confetti({
    particleCount: 80,
    spread: 60,
    origin: { y: 0.6 },
    colors: ['#a3e635', '#9333ea', '#0d9488'],
  });
};

const handleToggleHabit = async (habitId: number | string) => {
  if (!isDateEditable.value) return;
  const key = String(habitId);
  if (isToggling.value[key]) return;

  isToggling.value[key] = true;
  const wasCompleted = progressPercentage.value === 100;
  await triggerHaptic('light');

  try {
    await habitStore.toggleHabit(habitId, formattedDate.value);

    if (!wasCompleted && progressPercentage.value === 100) {
      await triggerHaptic('success');
      fireConfetti();
    }
  } catch (err: unknown) {
    await triggerHaptic('error');
    showToast('error', getErrorMessage(err, 'Erro ao atualizar hábito.'));
  } finally {
    isToggling.value[key] = false;
  }
};

const handleNavigateToHabit = (habitId: number | string) => {
  closeModal();
  router.push(`/habit/${habitId}`);
};

const handleCreateHabit = () => {
  closeModal();
  router.push('/habit');
};
</script>

<template>
  <ion-modal
    :is-open="isOpen"
    :breakpoints="[0, 0.5, 0.85]"
    :initial-breakpoint="0.5"
    handle-behavior="cycle"
    class="day-sheet-modal"
    @did-present="startTourIfPossible"
    @did-dismiss="closeModal"
  >
    <ion-content class="sheet-content ion-padding-horizontal">
      <div class="sheet-header-row">
        <Breadcrumb
          :week-day="dayOfWeek"
          :date="dayAndMonth"
        />
        <ion-button
          fill="clear"
          color="medium"
          aria-label="Fechar"
          class="sheet-close-btn"
          @click="closeModal"
        >
          <ion-icon slot="icon-only" :icon="closeOutline" />
        </ion-button>
      </div>

      <div class="sheet-body">
        <ProgressBar
          id="onboarding-progress-bar"
          :progress="progressPercentage"
          class="sheet-progress"
        />

        <div v-if="isLoading && !dayInfo.possible_habits.length" class="sheet-loading">
          <ion-spinner name="crescent" color="primary" />
        </div>

        <div v-else-if="dayInfo.possible_habits.length > 0" class="habits-checklist">
          <Checkbox
            v-for="(habit, index) in dayInfo.possible_habits"
            :id="index === 0 ? 'onboarding-habit-checkbox' : undefined"
            :key="habit.id"
            :label="habit.title"
            :is-checked="isHabitChecked(habit.id)"
            :is-disabled="!isDateEditable || isToggling[String(habit.id)]"
            @handle-item="handleNavigateToHabit(habit.id)"
            @handle-checkbox-change="handleToggleHabit(habit.id)"
          />
        </div>

        <div
          v-else-if="!isLoading && isDateEditable"
          class="sheet-empty ion-text-center ion-padding"
        >
          <ion-text color="medium">
            <p>Você não possui hábitos cadastrados para este dia.</p>
          </ion-text>
          <Button
            color="primary"
            size="small"
            class="ion-margin-top"
            @click="handleCreateHabit"
          >
            <ion-icon slot="start" :icon="addOutline" />
            Criar Hábito
          </Button>
        </div>

        <div v-if="!isDateEditable" class="sheet-locked-notice ion-text-center ion-padding-top">
          <ion-text color="medium">
            <small>Você pode alterar hábitos de hoje e de ontem. Datas anteriores estão bloqueadas para manter seu histórico.</small>
          </ion-text>
        </div>
      </div>
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="day"
      :steps="daySteps"
      :enabled="canStartTour"
    />
  </ion-modal>
</template>

<style scoped>
ion-modal.day-sheet-modal {
  --background: var(--color-background-secondary);
  --border-radius: 20px 20px 0 0;
}

.sheet-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-top: 16px;
  margin-bottom: 4px;
}

.sheet-close-btn {
  margin: 0 -8px 0 0;
  --padding-start: 10px;
  --padding-end: 10px;
  width: 48px;
  height: 48px;
}

.sheet-close-btn ion-icon {
  font-size: 2rem;
  color: var(--color-text-secondary);
  transition: color 0.15s ease;
}

.sheet-close-btn:hover ion-icon,
.sheet-close-btn:focus ion-icon {
  color: var(--color-text-primary);
}

.sheet-content {
  --background: var(--color-background-secondary);
}

.sheet-body {
  padding-bottom: 24px;
}

.sheet-progress {
  margin: 8px 0 16px;
}

.habits-checklist {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
}

.sheet-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 32px 0;
}

.sheet-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 16px;
}

.sheet-locked-notice {
  margin-top: 12px;
  border-top: 1px solid var(--color-border-default);
  padding-top: 12px;
}
</style>
