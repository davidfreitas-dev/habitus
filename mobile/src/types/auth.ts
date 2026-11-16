export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterUserData {
  name: string;
  email: string;
  password: string;
  website?: string;
  agreeToTerms?: boolean;
}

export interface AuthTokens {
  access_token: string;
  refresh_token?: string;
  token_type?: string;
  expires_in?: number;
}

export type SocialProvider = 'google';
