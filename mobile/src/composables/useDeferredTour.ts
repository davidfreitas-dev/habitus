import type { Ref } from 'vue';
import { onIonViewDidEnter, onIonViewWillEnter, onIonViewWillLeave } from '@ionic/vue';

interface TourHandle {
  startIfNotSeen: () => Promise<void>;
  dismiss?: () => void;
  finish?: () => void;
}

/**
 * Coordinates Ionic view lifecycle so an onboarding tour only starts
 * after the page's initial data-loading promise settles.
 */
export function useDeferredTour(
  tourRef: Ref<TourHandle | null>,
  onWillEnter: () => Promise<unknown> | void
) {
  let pendingLoad: Promise<unknown> | null = null;

  onIonViewWillEnter(() => {
    const result = onWillEnter();
    pendingLoad = result instanceof Promise ? result : null;
  });

  onIonViewDidEnter(async () => {
    if (pendingLoad) {
      try {
        await pendingLoad;
      } catch {
        // Errors are handled by the caller's loader
      }
    }
    await tourRef.value?.startIfNotSeen();
  });

  onIonViewWillLeave(() => {
    if (tourRef.value?.dismiss) {
      tourRef.value.dismiss();
    } else {
      tourRef.value?.finish?.();
    }
  });
}
