import api from '@/api';
import { AUTH_ENDPOINTS } from '@/constants/endpoints';
import type { ApiResponse, AuthTokens, LoginCredentials, RegisterUserData, SocialProvider } from '@/types';

export const authService = {
  login(credentials: LoginCredentials) {
    return api.post<ApiResponse<AuthTokens>>(AUTH_ENDPOINTS.LOGIN, credentials);
  },
  
  register(userData: RegisterUserData) {
    return api.post<ApiResponse<AuthTokens>>(AUTH_ENDPOINTS.REGISTER, userData);
  },
  
  socialLogin(provider: SocialProvider | string, idToken: string) {
    return api.post<ApiResponse<AuthTokens>>(AUTH_ENDPOINTS.SOCIAL_LOGIN(provider), { idToken });
  },
  
  logout() {
    return api.post<ApiResponse<null>>(AUTH_ENDPOINTS.LOGOUT);
  },
  
  refresh(refreshToken: string) {
    return api.post<ApiResponse<AuthTokens>>(AUTH_ENDPOINTS.REFRESH, { refresh_token: refreshToken });
  },
  
  forgotPassword(email: string) {
    return api.post<ApiResponse<{ message?: string }>>(AUTH_ENDPOINTS.FORGOT_PASSWORD, { email });
  },
  
  validateResetCode(email: string, code: string) {
    return api.post<ApiResponse<{ message?: string }>>(AUTH_ENDPOINTS.VALIDATE_RESET_CODE, { email, code });
  },
  
  resetPassword(email: string, code: string, password: string, passwordConfirm: string) {
    return api.post<ApiResponse<{ message?: string }>>(AUTH_ENDPOINTS.RESET_PASSWORD, { 
      email, 
      code, 
      password, 
      password_confirm: passwordConfirm 
    });
  },
  
  verifyEmail(token: string) {
    return api.get<ApiResponse<{ message?: string }>>(`${AUTH_ENDPOINTS.VERIFY_EMAIL}?token=${token}`);
  }
};
