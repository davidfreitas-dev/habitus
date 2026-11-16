import { ref } from 'vue';
import { defineStore } from 'pinia';
import { useAuthStore } from './authStore';
import { profileService } from '@/services/profileService';
import type { UpdateProfilePayload, UserProfile } from '@/types';

export const useProfileStore = defineStore('profile', () => {
  const user = ref<UserProfile | null>(null);
  const authStore = useAuthStore();

  const fetchProfile = async () => {
    if (!authStore.isAuthenticated) {
      user.value = null;
      return;
    }
    const data = await profileService.getProfile();
    user.value = data.data;
    return true;
  };

  const updateProfile = async (profileData: UpdateProfilePayload) => {
    const data = await profileService.updateProfile(profileData);
    user.value = data.data;
    return true;
  };

  const changePassword = async (currentPassword: string, newPassword: string, confirmNewPassword: string) => {
    return await profileService.changePassword(
      currentPassword,
      newPassword,
      confirmNewPassword
    );
  };

  const deleteAccount = async () => {
    return await profileService.deleteAccount();
  };

  const clearProfile = () => {
    user.value = null;
  };

  return {
    user,
    fetchProfile,
    updateProfile,
    changePassword,
    deleteAccount,
    clearProfile,
  };
}, {
  persist: true,
});
