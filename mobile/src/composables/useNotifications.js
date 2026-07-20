import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';

export function useNotifications() {
  const isNative = Capacitor.isNativePlatform();

  const checkPermission = async () => {
    if (!isNative) return true;

    const permissions = await LocalNotifications.checkPermissions();

    return permissions.display === 'granted';
  };

  const requestPermission = async () => {
    if (!isNative) return true;

    let permissions = await LocalNotifications.checkPermissions();

    if (permissions.display === 'granted') {
      return true;
    }

    permissions = await LocalNotifications.requestPermissions();

    return permissions.display === 'granted';
  };

  return {
    checkPermission,
    requestPermission,
  };
}

