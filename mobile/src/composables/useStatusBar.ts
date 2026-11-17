import { Capacitor, SystemBars, SystemBarsStyle as Style } from '@capacitor/core';

export function useStatusBar() {
  const isNative = Capacitor.isNativePlatform();

  const setStatusBar = async (style: Style = Style.Dark) => {
    if (!isNative) return;

    await SystemBars.setStyle({ style });
  };

  return { setStatusBar, Style };
}
