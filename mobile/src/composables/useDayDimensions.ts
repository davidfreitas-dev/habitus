import { ref, computed, onMounted, onUnmounted } from 'vue';

const WEEK_DAYS = 7;
const SCREEN_HORIZONTAL_PADDING = (32 * 2) / 5;
const MAX_CONTAINER_WIDTH = 600;
const HORIZONTAL_BREAKPOINT = 768;
const HORIZONTAL_DAY_SIZE = 40;

const screenDimensions = ref({
  width: typeof window !== 'undefined' ? window.innerWidth : 375,
  height: typeof window !== 'undefined' ? window.innerHeight : 667,
});

let activeSubscribers = 0;

const updateScreenDimensions = () => {
  screenDimensions.value = { width: window.innerWidth, height: window.innerHeight };
};

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

export function useDayDimensions() {
  onMounted(() => {
    if (activeSubscribers === 0) {
      updateScreenDimensions();
      window.addEventListener('resize', updateScreenDimensions);
    }
    activeSubscribers++;
  });

  onUnmounted(() => {
    activeSubscribers = Math.max(0, activeSubscribers - 1);
    if (activeSubscribers === 0) {
      window.removeEventListener('resize', updateScreenDimensions);
    }
  });

  return {
    daySize,
    isHorizontalLayout,
  };
}
