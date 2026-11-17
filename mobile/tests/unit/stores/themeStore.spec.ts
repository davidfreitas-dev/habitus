import { beforeEach, describe, expect, it } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useThemeStore } from '@/stores/themeStore';

describe('useThemeStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    document.body.className = '';
  });

  it('toggles dark mode and updates body class list', () => {
    const store = useThemeStore();

    expect(store.isDarkMode).toBe(false);
    expect(document.body.classList.contains('dark')).toBe(false);

    store.setDarkMode(true);

    expect(store.isDarkMode).toBe(true);
    expect(document.body.classList.contains('dark')).toBe(true);
    expect(store.isInitialLoad).toBe(false);

    store.setDarkMode(false);

    expect(store.isDarkMode).toBe(false);
    expect(document.body.classList.contains('dark')).toBe(false);
  });
});
