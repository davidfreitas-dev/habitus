import { toastController } from '@ionic/vue';
import { alertCircleOutline, checkmarkCircleOutline, informationCircleOutline } from 'ionicons/icons';

const TOAST_DURATION = 2500;

export type ToastStatus = 'success' | 'error' | 'info' | 'danger';

interface ToastConfigItem {
  color?: string;
  cssClass?: string;
  icon: string;
}

const TOAST_CONFIG: Record<ToastStatus, ToastConfigItem> = {
  success: {
    cssClass: 'toast-success',
    icon: checkmarkCircleOutline,
  },
  error: {
    cssClass: 'toast-error',
    icon: alertCircleOutline,
  },
  danger: {
    cssClass: 'toast-error',
    icon: alertCircleOutline,
  },
  info: {
    color: 'light',
    icon: informationCircleOutline,
  },
};

export function useToast() {
  const showToast = async (status: ToastStatus, message: string) => {
    const config = TOAST_CONFIG[status] ?? TOAST_CONFIG.info;

    const toast = await toastController.create({
      message,
      color: config.color,
      cssClass: config.cssClass,
      icon: config.icon,
      duration: TOAST_DURATION,
    });

    await toast.present();
  };

  const showUndoToast = async (message: string, onUndo: () => void) => {
    const toast = await toastController.create({
      message,
      color: 'dark',
      duration: 3500,
      buttons: [
        {
          text: 'Desfazer',
          role: 'cancel',
          handler: () => {
            onUndo();
          },
        },
      ],
    });

    await toast.present();
  };

  return {
    showToast,
    showUndoToast,
  };
}
