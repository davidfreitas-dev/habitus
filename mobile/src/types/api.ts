export interface ApiResponse<T = unknown> {
  status?: string;
  message?: string;
  data: T;
}

export function getErrorMessage(error: unknown, fallback = 'Ocorreu um erro inesperado.'): string {
  if (typeof error === 'object' && error !== null) {
    const err = error as {
      response?: {
        data?: {
          message?: string;
          data?: string[] | Record<string, unknown>;
        };
      };
      message?: string;
    };

    if (err.response?.data?.message) {
      return err.response.data.message;
    }

    if (Array.isArray(err.response?.data?.data) && typeof err.response?.data?.data[0] === 'string') {
      return err.response.data.data[0];
    }

    if (err.message) {
      return err.message;
    }
  }

  return fallback;
}
