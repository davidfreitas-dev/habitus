import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '@/stores/authStore';
import { authService } from '@/services/authService';
import { useProfileStore } from '@/stores/profileStore';
import { STORAGE_KEYS } from '@/constants/storage';

vi.mock('@/services/authService', () => ({
  authService: {
    login: vi.fn(),
    socialLogin: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    refresh: vi.fn(),
    forgotPassword: vi.fn(),
    validateResetCode: vi.fn(),
    resetPassword: vi.fn(),
    verifyEmail: vi.fn(),
  },
}));

vi.mock('@/stores/profileStore', () => {
  const fetchProfile = vi.fn().mockResolvedValue(true);
  const clearProfile = vi.fn();
  return {
    useProfileStore: () => ({
      fetchProfile,
      clearProfile,
    }),
  };
});

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('stores access and refresh tokens upon successful login', async () => {
    const store = useAuthStore();
    vi.mocked(authService.login).mockResolvedValue({
      data: {
        access_token: 'acc_123',
        refresh_token: 'ref_456',
      },
    });

    const success = await store.login({ email: 'test@example.com', password: 'password123' });

    expect(success).toBe(true);
    expect(store.accessToken).toBe('acc_123');
    expect(store.refreshToken).toBe('ref_456');
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)).toBe('ref_456');
    expect(useProfileStore().fetchProfile).toHaveBeenCalled();
  });

  it('clears tokens and profile on logout', async () => {
    const store = useAuthStore();
    store.setTokens('acc_123', 'ref_456');

    vi.mocked(authService.logout).mockResolvedValue({ data: null });

    await store.logout();

    expect(store.accessToken).toBeNull();
    expect(store.refreshToken).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)).toBeNull();
    expect(useProfileStore().clearProfile).toHaveBeenCalled();
  });

  it('handles expired session by setting sessionExpired flag and clearing tokens', () => {
    const store = useAuthStore();
    store.setTokens('acc_123', 'ref_456');

    store.handleSessionExpired();

    expect(store.sessionExpired).toBe(true);
    expect(store.accessToken).toBeNull();
    expect(store.refreshToken).toBeNull();
  });

  it('successfully refreshes access token', async () => {
    const store = useAuthStore();
    store.setTokens('old_acc', 'old_ref');

    vi.mocked(authService.refresh).mockResolvedValue({
      data: {
        access_token: 'new_acc',
        refresh_token: 'new_ref',
      },
    });

    const refreshed = await store.refreshAccessToken();

    expect(refreshed).toBe(true);
    expect(store.accessToken).toBe('new_acc');
    expect(store.refreshToken).toBe('new_ref');
  });

  it('manages password reset flow steps in localStorage', async () => {
    const store = useAuthStore();

    vi.mocked(authService.forgotPassword).mockResolvedValue({ message: 'OK', data: {} });
    await store.forgotPassword('user@habits.dev');
    expect(localStorage.getItem(STORAGE_KEYS.FORGOT_EMAIL)).toBe('user@habits.dev');

    vi.mocked(authService.validateResetCode).mockResolvedValue({ message: 'Valid', data: {} });
    await store.validateResetCode('123456');
    expect(localStorage.getItem(STORAGE_KEYS.RESET_EMAIL)).toBe('user@habits.dev');
    expect(localStorage.getItem(STORAGE_KEYS.RESET_CODE)).toBe('123456');

    vi.mocked(authService.resetPassword).mockResolvedValue({ message: 'Changed', data: {} });
    await store.resetPassword('newPass123', 'newPass123');
    expect(localStorage.getItem(STORAGE_KEYS.FORGOT_EMAIL)).toBeNull();
    expect(localStorage.getItem(STORAGE_KEYS.RESET_EMAIL)).toBeNull();
    expect(localStorage.getItem(STORAGE_KEYS.RESET_CODE)).toBeNull();
  });
});
