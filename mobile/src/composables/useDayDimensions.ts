import { ref, computed, onMounted, onUnmounted } from 'vue';

export function useDayDimensions() {
  const WEEK_DAYS = 7;
  const SCREEN_HORIZONTAL_PADDING = (32 * 2) / 5;

  const screenDimensions = ref({ width: window.innerWidth, height: window.innerHeight });

  const updateScreenDimensions = () => {
    screenDimensions.value = { width: window.innerWidth, height: window.innerHeight };
  };

  onMounted(() => {
    window.addEventListener('resize', updateScreenDimensions);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', updateScreenDimensions);
  });

  const daySize = computed(() => {
    const size = Math.trunc((screenDimensions.value.width / WEEK_DAYS) - SCREEN_HORIZONTAL_PADDING);
    return { width: size + 'px', height: size + 'px' };
  });

  return {
    daySize,
  };
}
