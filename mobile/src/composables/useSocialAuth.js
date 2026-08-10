import { SocialLogin } from '@capgo/capacitor-social-login';

export async function initSocialLogin() {
  const webClientId = import.meta.env.VITE_GOOGLE_WEB_CLIENT_ID;
  
  if (!webClientId) {
    console.warn('VITE_GOOGLE_WEB_CLIENT_ID não está definido nas variáveis de ambiente.');
  }

  await SocialLogin.initialize({
    google: {
      webClientId: webClientId || 'SEU_WEB_CLIENT_ID.apps.googleusercontent.com',
    }
  });
}

export async function loginWithGoogle() {
  try {
    const result = await SocialLogin.login({
      provider: 'google',
      options: {},
    });
    
    // result.result.idToken é o token que deve ser enviado para o backend
    return result.result.idToken;
  } catch (error) {
    console.error('Erro ao fazer login com o Google:', error);
    throw error;
  }
}
