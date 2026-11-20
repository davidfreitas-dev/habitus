/**
 * Horizontally centers a target element inside a scrollable container.
 */
export function centerElementInScrollContainer(
  container: HTMLElement | null,
  targetEl: HTMLElement | null
): void {
  if (!container || !targetEl) return;

  const targetScrollLeft =
    targetEl.offsetLeft - container.offsetWidth / 2 + targetEl.offsetWidth / 2;
  const clampedLeft = Math.max(0, targetScrollLeft);

  if (typeof container.scrollTo === 'function') {
    container.scrollTo({
      left: clampedLeft,
      behavior: 'smooth',
    });
  } else {
    container.scrollLeft = clampedLeft;
  }
}
