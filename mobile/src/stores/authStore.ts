import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { useProfileStore } from './profileStore';
import { authService } from '@/services/authService';
import { STORAGE_KEYS } from '@/constants/storage';
import type { LoginCredentials, RegisterUserData, SocialProvider } from '@/types';

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(null);
  const refreshToken = ref<string | null>(localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN) || null);
  const sessionExpired = ref<boolean>(false);

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
    useProfileStore().clearProfile();
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
    
    if (response.data?.access_token) {
      setTokens(response.data.access_token, response.data.refresh_token);
      await useProfileStore().fetchProfile();
      return true;
    }

    return false;
  };

  const socialLogin = async (provider: SocialProvider | string, idToken: string) => {
    const response = await authService.socialLogin(provider, idToken);
    
    if (response.data?.access_token) {
      setTokens(response.data.access_token, response.data.refresh_token);
      await useProfileStore().fetchProfile();
      return true;
    }

    return false;
  };

  const register = async (userData: RegisterUserData) => {
    const response = await authService.register(userData);
    
    if (response.data?.access_token) {
      setTokens(response.data.access_token, response.data.refresh_token);
      await useProfileStore().fetchProfile();
      return true;
    }

    return false;
  };

  const refreshAccessToken = async () => {
    try {
      if (!refreshToken.value) {
        throw new Error('No refresh token available');
      }
      const response = await authService.refresh(refreshToken.value);

      if (response.data?.access_token) {
        setTokens(response.data.access_token, response.data.refresh_token);
        await useProfileStore().fetchProfile();
        return true;
      }
    } catch (error) {
      // Refresh failed
    }

    clearTokens();
    return false;
  };

  const forgotPassword = async (email: string) => {
    const response = await authService.forgotPassword(email);
    localStorage.setItem(STORAGE_KEYS.FORGOT_EMAIL, email);
    return response;
  };

  const validateResetCode = async (code: string) => {
    const email = localStorage.getItem(STORAGE_KEYS.FORGOT_EMAIL);
    
    if (!email) {
      throw new Error('E-mail de recuperação não encontrado.');
    }

    const response = await authService.validateResetCode(email, code);
    
    localStorage.setItem(STORAGE_KEYS.RESET_EMAIL, email);
    localStorage.setItem(STORAGE_KEYS.RESET_CODE, code);

    return response;
  };

  const resetPassword = async (password: string, passwordConfirm: string) => {
    const email = localStorage.getItem(STORAGE_KEYS.RESET_EMAIL);
    const code = localStorage.getItem(STORAGE_KEYS.RESET_CODE);
    
    if (!email || !code) {
      throw new Error('Informações de recuperação incompletas.');
    }

    const response = await authService.resetPassword(email, code, password, passwordConfirm);
    
    localStorage.removeItem(STORAGE_KEYS.FORGOT_EMAIL);
    localStorage.removeItem(STORAGE_KEYS.RESET_EMAIL);
    localStorage.removeItem(STORAGE_KEYS.RESET_CODE);
    
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
