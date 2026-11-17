import axios, { type AxiosRequestConfig, type InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { AUTH_ENDPOINTS } from '@/constants/endpoints';

const BASE_URL = import.meta.env.VITE_BASE_URL;
const REQUEST_TIMEOUT = 10000;
const HTTP_STATUS = {
  UNAUTHORIZED: 401,
} as const;

interface CustomAxiosRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

interface QueuedRequest {
  resolve: (token: string | null) => void;
  reject: (err: unknown) => void;
}

const axiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: REQUEST_TIMEOUT,
  withCredentials: true,
});

let isRefreshingToken = false;
let requestsQueue: QueuedRequest[] = [];

const processRequestQueue = (error: unknown = null, newToken: string | null = null) => {
  requestsQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
    } else {
      resolve(newToken);
    }
  });
  requestsQueue = [];
};

const addAuthorizationHeader = (config: CustomAxiosRequestConfig): CustomAxiosRequestConfig => {
  const authStore = useAuthStore();
  if (authStore.accessToken) {
    config.headers.set('Authorization', `Bearer ${authStore.accessToken}`);
  }
  return config;
};

const handleTokenRefresh = async (originalRequest: CustomAxiosRequestConfig) => {
  const authStore = useAuthStore();
  
  try {
    const refreshSuccessful = await authStore.refreshAccessToken();

    if (!refreshSuccessful) {
      throw new Error('Token refresh failed');
    }

    const newAccessToken = authStore.accessToken;
    if (newAccessToken) {
      originalRequest.headers.set('Authorization', `Bearer ${newAccessToken}`);
    }
    processRequestQueue(null, newAccessToken);
    
    return axiosInstance(originalRequest);
  } catch (refreshError) {
    processRequestQueue(refreshError);
    authStore.handleSessionExpired();
    throw refreshError;
  } finally {
    isRefreshingToken = false;
  }
};

const queueRequest = async (originalRequest: CustomAxiosRequestConfig) => {
  const newToken = await new Promise<string | null>((resolve, reject) => {
    requestsQueue.push({ resolve, reject });
  });

  if (newToken) {
    originalRequest.headers.set('Authorization', `Bearer ${newToken}`);
  }
  return axiosInstance(originalRequest);
};

const isRefreshEndpointError = (error: unknown): boolean => {
  if (!axios.isAxiosError(error)) return false;
  return error.config?.url?.endsWith(AUTH_ENDPOINTS.REFRESH) ?? false;
};

const isPublicAuthEndpoint = (error: unknown): boolean => {
  if (!axios.isAxiosError(error)) return false;
  const publicAuthEndpoints: string[] = [
    AUTH_ENDPOINTS.LOGIN,
    AUTH_ENDPOINTS.REGISTER,
    AUTH_ENDPOINTS.FORGOT_PASSWORD,
    AUTH_ENDPOINTS.VALIDATE_RESET_CODE,
    AUTH_ENDPOINTS.RESET_PASSWORD,
  ];
  return publicAuthEndpoints.some(endpoint => error.config?.url?.endsWith(endpoint));
};

const shouldRetryWithRefresh = (error: unknown): boolean => {
  if (!axios.isAxiosError(error)) return false;
  const config = error.config as CustomAxiosRequestConfig | undefined;
  return (
    error.response?.status === HTTP_STATUS.UNAUTHORIZED &&
    !config?._retry &&
    !isRefreshEndpointError(error) &&
    !isPublicAuthEndpoint(error)
  );
};

axiosInstance.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const customConfig = addAuthorizationHeader(config as CustomAxiosRequestConfig);
    
    // Adiciona timestamp para evitar cache agressivo de GET requests em WebViews (produção)
    if (customConfig.method === 'get') {
      customConfig.params = {
        ...customConfig.params,
        _t: Date.now()
      };
    }
    
    return customConfig;
  },
  (error) => {
    console.error('Erro na requisição da API:', error);
    return Promise.reject(error);
  }
);

axiosInstance.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (isRefreshEndpointError(error)) {
      const authStore = useAuthStore();
      authStore.handleSessionExpired();
      return Promise.reject(error);
    }

    if (shouldRetryWithRefresh(error)) {
      const originalRequest = error.config as CustomAxiosRequestConfig;
      originalRequest._retry = true;

      if (isRefreshingToken) {
        return queueRequest(originalRequest);
      }

      isRefreshingToken = true;
      return handleTokenRefresh(originalRequest);
    }

    return Promise.reject(error);
  }
);

export interface CustomApiClient {
  get<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T>;
  post<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T>;
  put<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T>;
  patch<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T>;
  delete<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T>;
  <T = unknown>(config: AxiosRequestConfig): Promise<T>;
}

const api = axiosInstance as unknown as CustomApiClient;

export default api;
