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

  const MAX_CONTAINER_WIDTH = 600;
  const HORIZONTAL_BREAKPOINT = 768;
  const HORIZONTAL_DAY_SIZE = 40;

  const isHorizontalLayout = computed(
    () => screenDimensions.value.width >= HORIZONTAL_BREAKPOINT
  );

  const daySize = computed(() => {
    if (isHorizontalLayout.value) {
      return {
        width: `${HORIZONTAL_DAY_SIZE}px`,
        height: `${HORIZONTAL_DAY_SIZE}px`,
      };
    }

    const effectiveWidth = Math.min(screenDimensions.value.width, MAX_CONTAINER_WIDTH);
    const size = Math.trunc((effectiveWidth / WEEK_DAYS) - SCREEN_HORIZONTAL_PADDING);
    return { width: size + 'px', height: size + 'px' };
  });

  return {
    daySize,
    isHorizontalLayout,
  };
}
