import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';

export function useNotifications() {
  const isNative = Capacitor.isNativePlatform();

  const checkPermission = async (): Promise<boolean> => {
    if (!isNative) return true;

    const permissions = await LocalNotifications.checkPermissions();

    return permissions.display === 'granted';
  };

  const requestPermission = async (): Promise<boolean> => {
    if (!isNative) return true;

    let permissions = await LocalNotifications.checkPermissions();

    if (permissions.display === 'granted') {
      return true;
    }

    permissions = await LocalNotifications.requestPermissions();

    return permissions.display === 'granted';
  };

  const listenNotificationActionPerformed = async (
    callback: (habitId?: number | string) => void
  ) => {
    if (!isNative) return null;

    return await LocalNotifications.addListener(
      'localNotificationActionPerformed',
      (action) => {
        const habitId = action.notification.extra?.habitId;
        callback(habitId);
      }
    );
  };

  return {
    checkPermission,
    requestPermission,
    listenNotificationActionPerformed,
  };
}
