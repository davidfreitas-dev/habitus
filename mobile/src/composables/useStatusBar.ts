import { StatusBar, Style } from '@capacitor/status-bar';
import { Capacitor } from '@capacitor/core';

export function useStatusBar() {
  const isNative = Capacitor.isNativePlatform();

  const setStatusBar = async (style: Style = Style.Dark, color?: string) => {
    if (!isNative) return;

    if (color) await StatusBar.setBackgroundColor({ color });
    await StatusBar.setStyle({ style });
  };

  return { setStatusBar, Style };
}
