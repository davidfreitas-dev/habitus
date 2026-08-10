import { GoogleTagManager } from '@capgo/capacitor-gtm';

export async function initGTM() {
  const containerId = import.meta.env.VITE_GTM_CONTAINER_ID;
  
  if (!containerId) {
    console.warn('VITE_GTM_CONTAINER_ID não está definido nas variáveis de ambiente. O GTM não foi inicializado.');
    return;
  }

  try {
    await GoogleTagManager.initialize({ containerId, timeout: 2000 });
    console.log('GTM inicializado com sucesso.');
  } catch (error) {
    console.error('Erro ao inicializar o GTM:', error);
  }
}

export async function trackEvent(eventName, params = {}) {
  try {
    await GoogleTagManager.push({ event: eventName, parameters: params });
  } catch (error) {
    console.error(`Erro ao disparar evento ${eventName} no GTM:`, error);
  }
}

export async function setUserProperty(key, value) {
  try {
    await GoogleTagManager.setUserProperty({ key, value });
  } catch (error) {
    console.error(`Erro ao definir propriedade do usuário ${key} no GTM:`, error);
  }
}
