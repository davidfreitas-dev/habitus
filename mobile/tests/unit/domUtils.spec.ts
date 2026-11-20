import { describe, expect, test, vi } from 'vitest';
import { centerElementInScrollContainer } from '@/utils/domUtils';

describe('centerElementInScrollContainer', () => {
  test('ignores null container or target', () => {
    expect(() => centerElementInScrollContainer(null, null)).not.toThrow();
  });

  test('scrolls smoothly to centered offset when scrollTo is available', () => {
    const scrollTo = vi.fn();
    const container = { offsetWidth: 200, scrollTo } as unknown as HTMLElement;
    const target = { offsetLeft: 300, offsetWidth: 80 } as unknown as HTMLElement;

    centerElementInScrollContainer(container, target);

    // 300 - 200/2 + 80/2 = 240
    expect(scrollTo).toHaveBeenCalledWith({
      left: 240,
      behavior: 'smooth',
    });
  });

  test('clamps negative scroll positions to 0 and falls back to scrollLeft', () => {
    const container = { offsetWidth: 300, scrollLeft: 99 } as unknown as HTMLElement;
    const target = { offsetLeft: 10, offsetWidth: 40 } as unknown as HTMLElement;

    centerElementInScrollContainer(container, target);

    expect(container.scrollLeft).toBe(0);
  });
});
