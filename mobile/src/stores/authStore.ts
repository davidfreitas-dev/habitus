import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { useProfileStore } from './profileStore';
import { useHabitStore } from './habitsStore';
import { authService } from '@/services/authService';
import { syncQueueService } from '@/services/syncQueueService';
import { notificationService } from '@/services/notificationService';
import { STORAGE_KEYS } from '@/constants/storage';
import type { ApiResponse, AuthTokens, LoginCredentials, RegisterUserData, SocialProvider } from '@/types';

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(null);
  const refreshToken = ref<string | null>(localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN) || null);
  const sessionExpired = ref<boolean>(false);
  const resetFlowEmail = ref<string | null>(null);
  const resetFlowCode = ref<string | null>(null);
  const sessionId = ref<number>(1);

  const isAuthenticated = computed(() => !!accessToken.value);

  const setTokens = (access: string, refresh: string | null = null) => {
    accessToken.value = access;
    if (refresh) {
      refreshToken.value = refresh;
      localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refresh);
    }
  };

  const clearTokens = () => {
    accessToken.value = null;
    refreshToken.value = null;
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    sessionId.value++;
    useProfileStore().clearProfile();
    useHabitStore().clearHabitsState();
    void syncQueueService.clear();
    void notificationService.cancelAllNotifications();
  };

  const applyAuthResponse = async (response: ApiResponse<AuthTokens>, isNewSession = false): Promise<boolean> => {
    if (response.data?.access_token) {
      setTokens(response.data.access_token, response.data.refresh_token);
      if (isNewSession) {
        sessionId.value++;
      }
      await useProfileStore().fetchProfile();
      return true;
    }
    return false;
  };

  const logout = async () => {
    try {
      if (isAuthenticated.value) {
        await authService.logout();
      }

      return true;
    } finally {
      clearTokens();
    }
  };

  const handleSessionExpired = () => {
    sessionExpired.value = true;
    clearTokens();
  };

  const login = async (credentials: LoginCredentials) => {
    const response = await authService.login(credentials);
    return applyAuthResponse(response, true);
  };

  const socialLogin = async (provider: SocialProvider | string, idToken: string) => {
    const response = await authService.socialLogin(provider, idToken);
    return applyAuthResponse(response, true);
  };

  const register = async (userData: RegisterUserData) => {
    const response = await authService.register(userData);
    return applyAuthResponse(response, true);
  };

  const refreshAccessToken = async () => {
    try {
      if (!refreshToken.value) {
        throw new Error('No refresh token available');
      }
      const response = await authService.refresh(refreshToken.value);
      if (await applyAuthResponse(response)) {
        return true;
      }
    } catch {
      // Refresh failed
    }

    clearTokens();
    return false;
  };

  const forgotPassword = async (email: string) => {
    const response = await authService.forgotPassword(email);
    resetFlowEmail.value = email;
    return response;
  };

  const validateResetCode = async (code: string) => {
    const email = resetFlowEmail.value;

    if (!email) {
      throw new Error('E-mail de recuperação não encontrado.');
    }

    const response = await authService.validateResetCode(email, code);
    resetFlowCode.value = code;

    return response;
  };

  const resetPassword = async (password: string, passwordConfirm: string) => {
    const email = resetFlowEmail.value;
    const code = resetFlowCode.value;

    if (!email || !code) {
      throw new Error('Informações de recuperação incompletas.');
    }

    const response = await authService.resetPassword(email, code, password, passwordConfirm);

    resetFlowEmail.value = null;
    resetFlowCode.value = null;

    return response;
  };

  const verifyEmail = async (token: string) => {
    return await authService.verifyEmail(token);
  };

  return {
    accessToken,
    refreshToken,
    isAuthenticated,
    sessionExpired,
    resetFlowEmail,
    resetFlowCode,
    sessionId,
    setTokens,
    clearTokens,
    login,
    socialLogin,
    register,
    logout,
    handleSessionExpired,
    forgotPassword,
    validateResetCode,
    resetPassword,
    refreshAccessToken,
    verifyEmail,
  };
});
