import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useOfflineSync } from '@/composables/useOfflineSync';

const mockIsConnected = ref(true);

const mockToast = {
  showToast: vi.fn(),
};

const mockAuthStore = {
  isAuthenticated: true,
};

const mockHabitStore = {
  isSyncing: false,
  pendingSyncCount: 0,
  initPendingSyncCount: vi.fn().mockResolvedValue(0),
  syncPendingHabits: vi.fn().mockResolvedValue({ synced: 2, failed: 0 }),
};

vi.mock('@/composables/useNetwork', () => ({
  useNetwork: vi.fn(() => ({
    isConnected: mockIsConnected,
  })),
}));

vi.mock('@/composables/useToast', () => ({
  useToast: vi.fn(() => mockToast),
}));

vi.mock('@/stores/authStore', () => ({
  useAuthStore: vi.fn(() => mockAuthStore),
}));

vi.mock('@/stores/habitsStore', () => ({
  useHabitStore: vi.fn(() => mockHabitStore),
}));

vi.mock('@capacitor/app', () => ({
  App: {
    addListener: vi.fn().mockResolvedValue({ remove: vi.fn() }),
  },
}));

describe('useOfflineSync', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockIsConnected.value = true;
    mockHabitStore.isSyncing = false;
    mockAuthStore.isAuthenticated = true;
  });

  it('triggers syncNow and shows toast when items are synced', async () => {
    const { syncNow } = useOfflineSync();

    await syncNow();

    expect(mockHabitStore.syncPendingHabits).toHaveBeenCalled();
    expect(mockToast.showToast).toHaveBeenCalledWith('success', '2 hábitos sincronizados com sucesso.');
  });

  it('does not trigger sync if not connected or habitStore is already syncing', async () => {
    mockIsConnected.value = false;
    const { syncNow } = useOfflineSync();

    await syncNow();

    expect(mockHabitStore.syncPendingHabits).not.toHaveBeenCalled();
  });
});
