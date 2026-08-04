import api from '@/api';
import { PROFILE_ENDPOINTS } from '@/constants/endpoints';

export const profileService = {
  getProfile() {
    return api.get(PROFILE_ENDPOINTS.BASE);
  },
  
  updateProfile(profileData) {
    return api.put(PROFILE_ENDPOINTS.BASE, profileData);
  },
  
  changePassword(currentPassword, newPassword, confirmNewPassword) {
    return api.patch(PROFILE_ENDPOINTS.CHANGE_PASSWORD, {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirm: confirmNewPassword,
    });
  },
  
  deleteAccount() {
    return api.delete(PROFILE_ENDPOINTS.BASE);
  }
};
