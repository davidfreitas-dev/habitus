<script setup lang="ts">
import {
  IonPage,
  IonContent,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  onIonViewWillEnter,
  onIonViewDidEnter,
  onIonViewDidLeave,
  type RefresherCustomEvent,
} from '@ionic/vue';
import { ref, computed, watch } from 'vue';
import { useHabitStore } from '@/stores/habitsStore';
import { useToast } from '@/composables/useToast';
import { statsSteps } from '@/onboarding/statsSteps';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import HabitBarChart from '@/components/features/habits/HabitBarChart.vue';
import HabitPeriodSelector from '@/components/features/habits/HabitPeriodSelector.vue';
import OnboardingTour from '@/components/features/onboarding/OnboardingTour.vue';
import dayjs from '@/lib/dayjs';
import { getErrorMessage, type DailyStat, type StatsPeriod } from '@/types';

const ERROR_MSG = 'Erro ao carregar estatísticas';
const today = () => dayjs().format('YYYY-MM-DD');

const activePeriod = ref<StatsPeriod>('W');
const statsData = ref<DailyStat[]>([]);
const currentStreak = ref(0);
const longestStreak = ref(0);
const isLoadingStats = ref(false);

const habitStore = useHabitStore();
const { showToast } = useToast();

const chartLabels = computed(() => statsData.value.map(item => item.label));
const chartValues = computed(() => statsData.value.map(item => item.percentage || 0));

const averagePercentage = computed(() => {
  if (statsData.value.length === 0) return 0;

  const totalCompleted = statsData.value.reduce((acc, item) => acc + (item.completed || 0), 0);
  const totalHabits = statsData.value.reduce((acc, item) => acc + (item.total || 0), 0);

  if (totalHabits === 0) return 0;
  return Math.round((totalCompleted / totalHabits) * 100);
});

const displayedPercentage = ref(0);

watch(averagePercentage, (newVal) => {
  const duration = 1000;
  const startValue = displayedPercentage.value;
  const diff = newVal - startValue;
  const startTime = Date.now();

  const step = () => {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 4);

    displayedPercentage.value = Math.round(startValue + diff * ease);

    if (progress < 1) requestAnimationFrame(step);
  };

  requestAnimationFrame(step);
}, { immediate: true });

const loadStats = async (period: StatsPeriod | string) => {
  isLoadingStats.value = true;
  try {
    const result = await habitStore.getHabitStats(period, today());
    statsData.value = result.daily_stats;
    currentStreak.value = result.current_streak;
    longestStreak.value = result.longest_streak;
  } catch (err: unknown) {
    console.error('Error loading stats:', err);
    showToast('error', getErrorMessage(err, ERROR_MSG));
  } finally {
    isLoadingStats.value = false;
  }
};

watch(activePeriod, async (period) => {
  await loadStats(period);
});

const handleRefresh = async (event: RefresherCustomEvent) => {
  try {
    await loadStats(activePeriod.value);
  } finally {
    await event.target.complete();
  }
};

let statsLoaded: Promise<void> | null = null;

onIonViewWillEnter(() => {
  statsLoaded = loadStats(activePeriod.value);
});

onIonViewDidLeave(() => {
  statsData.value = [];
});

const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

onIonViewDidEnter(async () => {
  if (statsLoaded) {
    try {
      await statsLoaded;
    } catch {
      // Ignora o erro
    }
  }
  await onboardingTourRef.value?.startIfNotSeen();
});
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppHeading title="Estatísticas" class="ion-padding-horizontal ion-padding-top" />
    </AppHeader>
    <ion-content>
      <ion-refresher slot="fixed" @ion-refresh="handleRefresh">
        <ion-refresher-content />
      </ion-refresher>

      <AppContainer>
        <div class="stats-container">
          <HabitPeriodSelector id="onboarding-period-selector" v-model="activePeriod" />

          <p class="chart-description">
            O gráfico abaixo mostra sua taxa de conclusão média para cada dia da semana no período selecionado.
          </p>
          
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Total de Atividades</span>
              <h2 class="chart-percentage">
                {{ displayedPercentage }}%
              </h2>
            </div>

            <div v-if="isLoadingStats && statsData.length === 0" class="chart-skeleton">
              <ion-skeleton-text
                v-for="bar in 7"
                :key="bar"
                :animated="true"
                class="chart-skeleton-bar"
              />
            </div>

            <HabitBarChart
              v-else-if="statsData.length > 0"
              :data="chartValues"
              :labels="chartLabels"
            />
          </div>

          <div id="onboarding-streaks-grid" class="streaks-grid">
            <div class="streak-card">
              <div class="streak-header">
                <span class="streak-label">Sequência Atual</span>
                <div class="streak-icon-container">
                  <span class="streak-emoji">🔥</span>
                </div>
              </div>
              <div class="streak-value-container">
                <span class="streak-value">{{ currentStreak }}</span>
                <span class="streak-unit">dias</span>
              </div>
            </div>
            <div class="streak-card">
              <div class="streak-header">
                <span class="streak-label">Recorde</span>
                <div class="streak-icon-container">
                  <span class="streak-emoji">🏆</span>
                </div>
              </div>
              <div class="streak-value-container">
                <span class="streak-value">{{ longestStreak }}</span>
                <span class="streak-unit">dias</span>
              </div>
            </div>
          </div>
        </div>
      </AppContainer>
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="stats"
      :steps="statsSteps"
    />
  </ion-page>
</template>

<style scoped>
.chart-description {
  color: var(--color-text-secondary);
  font-size: 14px;
  margin: 0;
}

.chart-card {
  background: var(--color-background-secondary);
  border-radius: var(--radius-2xl);
  padding: 24px;
  color: var(--color-text-primary);
  min-height: 340px;
}

.chart-header {
  margin-bottom: 24px;
}

.chart-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--color-text-secondary);
}

.chart-percentage {
  font-size: 32px;
  font-weight: 800;
  margin: 4px 0 0 0;
  color: var(--color-text-primary);
}

.chart-skeleton {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 200px;
  gap: 12px;
  padding-top: 16px;
}

.chart-skeleton-bar {
  flex: 1;
  height: 140px;
  border-radius: 8px;
}

.stats-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.streaks-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.streak-card {
  background: var(--color-background-secondary);
  border-radius: var(--radius-2xl);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
}

.streak-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.streak-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

.streak-icon-container {
  background: var(--color-background-secondary);
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.streak-emoji {
  font-size: 16px;
}

.streak-value-container {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.streak-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--color-text-primary);
}

.streak-unit {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-secondary);
}
</style>
