import { Network } from '@capacitor/network';

export const isNetworkError = (error: unknown): boolean => {
  if (!error || typeof error !== 'object') {
    return false;
  }

  const err = error as {
    isAxiosError?: boolean;
    response?: unknown;
    code?: string;
    message?: string;
  };

  // If there is an HTTP response from the server, it was reached (not a network connectivity error)
  if (err.response) {
    return false;
  }

  // Axios network error, timeout, or aborted
  if (err.isAxiosError) {
    return true;
  }

  if (err.code === 'ERR_NETWORK' || err.code === 'ECONNABORTED' || err.code === 'ETIMEDOUT') {
    return true;
  }

  if (typeof err.message === 'string') {
    const msg = err.message.toLowerCase();
    if (
      msg.includes('network error') ||
      msg.includes('networkerror') ||
      msg.includes('timeout') ||
      msg.includes('failed to fetch') ||
      msg.includes('connection refused')
    ) {
      return true;
    }
  }

  return false;
};

export const checkIsOnline = async (): Promise<boolean> => {
  try {
    const status = await Network.getStatus();
    return Boolean(status.connected);
  } catch {
    return typeof navigator !== 'undefined' ? navigator.onLine : true;
  }
};
