export const STORAGE_KEYS = {
  ACCESS_TOKEN: 'access_token',
  REFRESH_TOKEN: 'refresh_token',
  FORGOT_EMAIL: 'forgot_password_email',
  RESET_EMAIL: 'reset_code_email',
  RESET_CODE: 'reset_code_value',
} as const;

export type StorageKey = typeof STORAGE_KEYS[keyof typeof STORAGE_KEYS];
