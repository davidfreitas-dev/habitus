import { onUnmounted, ref, watch, type Ref } from 'vue';

/**
 * Smoothly animates a numeric ref toward `target` using an ease-out quartic curve,
 * automatically cancelling any pending animation frame on change or unmount.
 */
export function useAnimatedNumber(target: Ref<number>, duration = 1000) {
  const displayed = ref(0);
  let frameId: number | null = null;

  const cancelPending = () => {
    if (frameId !== null) {
      cancelAnimationFrame(frameId);
      frameId = null;
    }
  };

  watch(
    target,
    (newVal) => {
      cancelPending();
      const startValue = displayed.value;
      const diff = newVal - startValue;
      const startTime = Date.now();

      const step = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 4);

        displayed.value = Math.round(startValue + diff * ease);

        if (progress < 1) {
          frameId = requestAnimationFrame(step);
        } else {
          frameId = null;
        }
      };

      frameId = requestAnimationFrame(step);
    },
    { immediate: true }
  );

  onUnmounted(cancelPending);

  return displayed;
}
