import { SocialLogin } from '@capgo/capacitor-social-login';

export async function initSocialLogin(): Promise<void> {
  const webClientId = import.meta.env.VITE_GOOGLE_WEB_CLIENT_ID;

  if (!webClientId) {
    console.warn('VITE_GOOGLE_WEB_CLIENT_ID não está definido nas variáveis de ambiente.');
    return;
  }

  await SocialLogin.initialize({
    google: {
      webClientId,
    },
  });
}

export async function loginWithGoogle(): Promise<string> {
  try {
    const result = await SocialLogin.login({
      provider: 'google',
      options: {},
    });
    
    return (result.result as { idToken: string }).idToken;
  } catch (error) {
    console.error('Erro ao fazer login com o Google:', error);
    throw error;
  }
}
