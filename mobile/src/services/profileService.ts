import api from '@/api';
import { PROFILE_ENDPOINTS } from '@/constants/endpoints';
import type { ApiResponse, UpdateProfilePayload, UserProfile } from '@/types';

export const profileService = {
  getProfile() {
    return api.get<ApiResponse<UserProfile>>(PROFILE_ENDPOINTS.BASE);
  },
  
  updateProfile(profileData: UpdateProfilePayload) {
    return api.put<ApiResponse<UserProfile>>(PROFILE_ENDPOINTS.BASE, profileData);
  },
  
  changePassword(currentPassword: string, newPassword: string, confirmNewPassword: string) {
    return api.patch<ApiResponse<{ message?: string }>>(PROFILE_ENDPOINTS.CHANGE_PASSWORD, {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirm: confirmNewPassword,
    });
  },
  
  deleteAccount() {
    return api.delete<ApiResponse<null>>(PROFILE_ENDPOINTS.BASE);
  }
};
