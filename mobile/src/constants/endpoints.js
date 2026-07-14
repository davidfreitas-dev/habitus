export const AUTH_ENDPOINTS = {
  LOGIN: '/auth/login',
  REGISTER: '/auth/register',
  LOGOUT: '/auth/logout',
  FORGOT_PASSWORD: '/auth/forgot-password',
  VALIDATE_RESET_CODE: '/auth/validate-reset-code',
  RESET_PASSWORD: '/auth/reset-password',
  REFRESH: '/auth/refresh',
  VERIFY_EMAIL: '/auth/verify-email',
};

export const HABIT_ENDPOINTS = {
  BASE: '/habits',
  DAY: '/habits/day',
  SUMMARY: '/habits/summary',
  STATS: '/habits/stats',
  DETAILS: (id) => `/habits/${id}`,
  TOGGLE: (id) => `/habits/${id}/toggle`,
};

export const PROFILE_ENDPOINTS = {
  BASE: '/profile',
  CHANGE_PASSWORD: '/profile/change-password',
};
