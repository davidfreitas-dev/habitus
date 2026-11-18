<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Bar } from 'vue-chartjs';
import { 
  Chart as ChartJS, 
  Title, 
  Tooltip, 
  Legend, 
  BarElement, 
  CategoryScale, 
  LinearScale,
  type ChartData,
  type ChartOptions
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps<{
  data: number[];
  labels: string[];
}>();

const getCSSVar = (name: string, fallback = ''): string => 
  getComputedStyle(document.body).getPropertyValue(name).trim() || fallback;

const BAR_DEFAULTS = {
  borderRadius: 20,
  borderSkipped: false as const,
  barThickness: 28,
  categoryPercentage: 0.8,
  barPercentage: 0.9,
  grouped: false,
};

const primaryColor = ref(getCSSVar('--color-primary'));
const backgroundColor = ref(getCSSVar('--color-background-elevated'));
const tickColor = ref(getCSSVar('--color-neutral-500', '#a1a1aa'));

let observer: MutationObserver | null = null;

onMounted(() => {
  observer = new MutationObserver(() => {
    primaryColor.value = getCSSVar('--color-primary');
    backgroundColor.value = getCSSVar('--color-background-elevated');
    tickColor.value = getCSSVar('--color-neutral-500', '#a1a1aa');
  });
  observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => observer?.disconnect());

const chartData = computed<ChartData<'bar'>>(() => ({
  labels: [...props.labels],
  datasets: [
    {
      ...BAR_DEFAULTS,
      data: [...props.data],
      backgroundColor: primaryColor.value,
      order: 1,
      animation: { duration: 1000, easing: 'easeOutQuart' }
    },
    {
      ...BAR_DEFAULTS,
      data: props.data.map(() => 100),
      backgroundColor: backgroundColor.value,
      order: 2,
      animation: { duration: 0 }
    }
  ]
}));

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      enabled: true,
      filter: (item) => item.datasetIndex === 0,
      callbacks: { label: (ctx) => `${ctx.raw}%` }
    }
  },
  scales: {
    y: { display: false, beginAtZero: true, max: 100 },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: {
        color: tickColor.value,
        font: { size: 16, weight: 'bold' }
      }
    }
  }
}));
</script>

<template>
  <div class="chart-container">
    <Bar
      :options="chartOptions"
      :data="chartData"
    />
  </div>
</template>

<style scoped>
.chart-container {
  width: 100%;
  height: 200px;
  position: relative;
}
</style>
