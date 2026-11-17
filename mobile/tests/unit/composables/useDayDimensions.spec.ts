import { describe, expect, it } from 'vitest';
import { useDayDimensions } from '@/composables/useDayDimensions';

describe('useDayDimensions', () => {
  it('computes square dimension for days respecting container limits', () => {
    const { daySize } = useDayDimensions();

    expect(daySize.value).toHaveProperty('width');
    expect(daySize.value).toHaveProperty('height');
    expect(daySize.value.width).toBe(daySize.value.height);
    expect(daySize.value.width.endsWith('px')).toBe(true);

    const numericSize = parseInt(daySize.value.width, 10);
    expect(numericSize).toBeGreaterThan(0);
  });
});
