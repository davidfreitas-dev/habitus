<script setup>
import { IonPage, IonContent, onIonViewWillEnter, onIonViewDidEnter, onIonViewDidLeave } from '@ionic/vue';
import { ref, computed, watch, nextTick } from 'vue';
import { useVOnboarding, VOnboardingStep, VOnboardingWrapper } from 'v-onboarding';
import { useHabitStore } from '@/stores/habits';
import { useLoading } from '@/composables/useLoading';
import { useOnboarding } from '@/composables/useOnboarding';
import { statsSteps } from '@/onboarding/statsSteps';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import BarChart from '@/components/habits/BarChart.vue';
import PeriodSelector from '@/components/habits/PeriodSelector.vue';
import OnboardingStep from '@/components/onboarding/OnboardingStep.vue';
import dayjs from '@/lib/dayjs';

// Ajuste os caminhos de import de `onboarding/statsSteps` e
// `components/onboarding/OnboardingStep.vue` para o local real
// onde esses arquivos ficaram no seu projeto.

const ERROR_MSG = 'Erro ao carregar estatísticas';
const today = () => dayjs().format('YYYY-MM-DD');

const activePeriod = ref('W');
const statsData = ref([]);
const currentStreak = ref(0);
const longestStreak = ref(0);
const habitStore = useHabitStore();
const { withLoading } = useLoading();

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

const loadStats = async (period) => {
  const result = await habitStore.getHabitStats(period, today());
  statsData.value = result.daily_stats;
  currentStreak.value = result.current_streak;
  longestStreak.value = result.longest_streak;
};

watch(activePeriod, (period) =>
  withLoading(() => loadStats(period), ERROR_MSG));

onIonViewWillEnter(() =>
  withLoading(() => loadStats(activePeriod.value), ERROR_MSG));

onIonViewDidLeave(() => {
  statsData.value = [];
});

// --- Onboarding: tour da tela de Estatísticas ---
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref(null);
const { start: startStatsOnboarding, finish: finishStatsOnboarding } = useVOnboarding(onboardingWrapper);

const onStatsOnboardingComplete = () => {
  markStepSeen('stats');
};

const onStatsOnboardingExit = () => {
  finishStatsOnboarding();
};

onIonViewDidEnter(async () => {
  if (await isStepSeen('stats')) return;
  startStatsOnboarding();
});
</script>

<template>
  <ion-page>
    <Header>
      <Heading title="Estatísticas" class="ion-padding-horizontal" />
    </Header>
    <ion-content>
      <Container>
        <PeriodSelector v-model="activePeriod" />

        <div class="stats-container">
          <p class="chart-description">
            Sua taxa de conclusão média para cada dia da semana no período selecionado.
          </p>
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Total de Atividades</span>
              <h2 class="chart-percentage">
                {{ displayedPercentage }}%
              </h2>
            </div>
            
            <BarChart 
              v-if="statsData.length > 0"
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
      </Container>
    </ion-content>

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="statsSteps"
      @finish="onStatsOnboardingComplete"
      @exit="onStatsOnboardingExit"
    >
      <template #default="{ step, isLast, next, exit }">
        <VOnboardingStep>
          <OnboardingStep
            :step="step"
            :index="statsSteps.indexOf(step)"
            :is-last="isLast"
            :total="statsSteps.length"
            @next="isLast ? exit() : next()"
            @skip="exit()"
          />
        </VOnboardingStep>
      </template>
    </VOnboardingWrapper>
  </ion-page>
</template>

<style scoped>
.chart-description {
  color: var(--color-text-secondary);
  font-size: 14px;
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