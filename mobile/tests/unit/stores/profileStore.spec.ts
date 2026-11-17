import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useProfileStore } from '@/stores/profileStore';
import { profileService } from '@/services/profileService';
import type { UserProfile } from '@/types';

vi.mock('@/services/profileService', () => ({
  profileService: {
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
    changePassword: vi.fn(),
    deleteAccount: vi.fn(),
  },
}));

vi.mock('@/stores/authStore', () => ({
  useAuthStore: () => ({
    isAuthenticated: true,
  }),
}));

describe('useProfileStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches profile data and updates user state', async () => {
    const store = useProfileStore();
    const mockUser: UserProfile = {
      id: 1,
      name: 'David Freitas',
      email: 'david@habits.dev',
      is_active: true,
      is_verified: true,
    };

    vi.mocked(profileService.getProfile).mockResolvedValue({ data: mockUser });

    await store.fetchProfile();

    expect(store.user).toEqual(mockUser);
  });

  it('updates profile state after editing', async () => {
    const store = useProfileStore();
    const updatedUser: UserProfile = {
      id: 1,
      name: 'David F.',
      email: 'novo@habits.dev',
    };

    vi.mocked(profileService.updateProfile).mockResolvedValue({ data: updatedUser });

    await store.updateProfile({ name: 'David F.', email: 'novo@habits.dev' });

    expect(store.user).toEqual(updatedUser);
  });

  it('clears profile state', () => {
    const store = useProfileStore();
    store.user = { id: 1, name: 'Test', email: 'test@habits.dev' };

    store.clearProfile();

    expect(store.user).toBeNull();
  });
});
