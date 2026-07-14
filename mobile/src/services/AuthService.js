import api from '@/api';
import { AUTH_ENDPOINTS } from '@/constants/endpoints';

export const AuthService = {
  login(credentials) {
    return api.post(AUTH_ENDPOINTS.LOGIN, credentials);
  },
  
  register(userData) {
    return api.post(AUTH_ENDPOINTS.REGISTER, userData);
  },
  
  logout() {
    return api.post(AUTH_ENDPOINTS.LOGOUT);
  },
  
  refresh(refreshToken) {
    return api.post(AUTH_ENDPOINTS.REFRESH, { refresh_token: refreshToken });
  },
  
  forgotPassword(email) {
    return api.post(AUTH_ENDPOINTS.FORGOT_PASSWORD, { email });
  },
  
  validateResetCode(email, code) {
    return api.post(AUTH_ENDPOINTS.VALIDATE_RESET_CODE, { email, code });
  },
  
  resetPassword(email, code, password, passwordConfirm) {
    return api.post(AUTH_ENDPOINTS.RESET_PASSWORD, { 
      email, 
      code, 
      password, 
      password_confirm: passwordConfirm 
    });
  },
  
  verifyEmail(token) {
    return api.get(`${AUTH_ENDPOINTS.VERIFY_EMAIL}?token=${token}`);
  }
};
