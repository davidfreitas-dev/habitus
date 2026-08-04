import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { useProfileStore } from './profileStore';
import { authService } from '@/services/authService';
import { STORAGE_KEYS } from '@/constants/storage';

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref(null);
  const sessionExpired = ref(false);

  const isAuthenticated = computed(() => !!accessToken.value);

  const setTokens = (access) => {
    accessToken.value = access;
  };

  const clearTokens = () => {
    setTokens(null);
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

  const login = async (credentials) => {
    const data = await authService.login(credentials);
    
    if (data.data?.access_token) {
      setTokens(data.data.access_token);
      await useProfileStore().fetchProfile();
      return true;
    }

    return false;
  };

  const register = async (userData) => {
    const data = await authService.register(userData);
    
    if (data.data?.access_token) {
      setTokens(data.data.access_token);
      await useProfileStore().fetchProfile();
      return true;
    }

    return false;
  };

  const refreshAccessToken = async () => {
    try {
      const data = await authService.refresh();

      if (data.data?.access_token) {
        setTokens(data.data.access_token);
        await useProfileStore().fetchProfile();
        return true;
      }
    } catch (error) {
      // Refresh failed
    }

    clearTokens();
    return false;
  };

  const forgotPassword = async (email) => {
    const response = await authService.forgotPassword(email);
    localStorage.setItem(STORAGE_KEYS.FORGOT_EMAIL, email);
    return response;
  };

  const validateResetCode = async (code) => {
    const email = localStorage.getItem(STORAGE_KEYS.FORGOT_EMAIL);
    
    if (!email) {
      throw new Error('E-mail de recuperação não encontrado.');
    }

    const response = await authService.validateResetCode(email, code);
    
    localStorage.setItem(STORAGE_KEYS.RESET_EMAIL, email);
    localStorage.setItem(STORAGE_KEYS.RESET_CODE, code);

    return response;
  };

  const resetPassword = async (password, passwordConfirm) => {
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

  const verifyEmail = async (token) => {
    return await authService.verifyEmail(token);
  };

  return {
    accessToken,
    isAuthenticated,
    sessionExpired,
    setTokens,
    clearTokens,
    login,
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

