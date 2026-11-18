import { describe, expect, it } from 'vitest';
import { isNetworkError } from '@/services/networkUtils';

describe('isNetworkError', () => {
  it('returns false for null, undefined, or non-object errors', () => {
    expect(isNetworkError(null)).toBe(false);
    expect(isNetworkError(undefined)).toBe(false);
    expect(isNetworkError('some string')).toBe(false);
  });

  it('returns false when error has an HTTP response (server reached)', () => {
    const serverError = {
      response: {
        status: 400,
        data: { message: 'Invalid data' },
      },
    };
    expect(isNetworkError(serverError)).toBe(false);

    const unauthorizedError = {
      response: {
        status: 401,
        data: { message: 'Unauthorized' },
      },
    };
    expect(isNetworkError(unauthorizedError)).toBe(false);
  });

  it('returns true when error is an Axios network error without response', () => {
    const axiosNetworkError = {
      isAxiosError: true,
      message: 'Network Error',
    };
    expect(isNetworkError(axiosNetworkError)).toBe(true);
  });

  it('returns true when error code is network timeout or abort', () => {
    expect(isNetworkError({ code: 'ERR_NETWORK' })).toBe(true);
    expect(isNetworkError({ code: 'ECONNABORTED' })).toBe(true);
    expect(isNetworkError({ code: 'ETIMEDOUT' })).toBe(true);
  });

  it('returns true when message mentions network error or timeout', () => {
    expect(isNetworkError(new Error('Network error occurred'))).toBe(true);
    expect(isNetworkError(new Error('timeout of 10000ms exceeded'))).toBe(true);
    expect(isNetworkError(new Error('Failed to fetch'))).toBe(true);
  });
});
