import { ref } from 'vue';
import { loadingController } from '@ionic/vue';
import { useToast } from '@/composables/useToast';
import { getErrorMessage } from '@/types';

export function useLoading() {
  const { showToast } = useToast();
  
  const isLoading = ref(false);

  const withLoading = async <T>(fn: () => Promise<T>, errorMessage = 'Ocorreu um erro.'): Promise<T> => {
    isLoading.value = true;

    const loading = await loadingController.create({
      mode: 'md',
      spinner: 'crescent',
      cssClass: 'custom-loading-spinner',
      showBackdrop: true,
      translucent: true
    });

    await loading.present();

    try {
      return await fn();
    } catch (err: unknown) {
      console.error('Erro na requisição:', err);
      const message = getErrorMessage(err, errorMessage);
      showToast('error', message);
      throw err;
    } finally {
      isLoading.value = false;
      await loading.dismiss();
    }
  };

  return {
    isLoading,
    withLoading
  };
}
