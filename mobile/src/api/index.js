import axios from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { AUTH_ENDPOINTS } from '@/constants/endpoints';

const BASE_URL = import.meta.env.VITE_BASE_URL;
const REQUEST_TIMEOUT = 10000;
const HTTP_STATUS = {
  UNAUTHORIZED: 401,
};

const api = axios.create({
  baseURL: BASE_URL,
  timeout: REQUEST_TIMEOUT,
  withCredentials: true,
});

let isRefreshingToken = false;
let requestsQueue = [];

const processRequestQueue = (error = null, newToken = null) => {
  requestsQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
    } else {
      resolve(newToken);
    }
  });
  requestsQueue = [];
};

const addAuthorizationHeader = (config) => {
  const authStore = useAuthStore();
  if (authStore.accessToken) {
    config.headers['Authorization'] = `Bearer ${authStore.accessToken}`;
  }
  return config;
};

const handleTokenRefresh = async (originalRequest) => {
  const authStore = useAuthStore();
  
  try {
    const refreshSuccessful = await authStore.refreshAccessToken();

    if (!refreshSuccessful) {
      throw new Error('Token refresh failed');
    }

    const newAccessToken = authStore.accessToken;
    originalRequest.headers['Authorization'] = `Bearer ${newAccessToken}`;
    processRequestQueue(null, newAccessToken);
    
    return api(originalRequest);
  } catch (refreshError) {
    processRequestQueue(refreshError);
    authStore.handleSessionExpired();
    throw refreshError;
  } finally {
    isRefreshingToken = false;
  }
};

const queueRequest = async (originalRequest) => {
  const newToken = await new Promise((resolve, reject) => {
    requestsQueue.push({ resolve, reject });
  });

  originalRequest.headers['Authorization'] = `Bearer ${newToken}`;
  return api(originalRequest);
};

const isRefreshEndpointError = (error) => {
  return error.config?.url?.endsWith(AUTH_ENDPOINTS.REFRESH);
};

const isPublicAuthEndpoint = (error) => {
  const publicAuthEndpoints = [
    AUTH_ENDPOINTS.LOGIN,
    AUTH_ENDPOINTS.REGISTER,
    AUTH_ENDPOINTS.FORGOT_PASSWORD,
    AUTH_ENDPOINTS.VALIDATE_RESET_CODE,
    AUTH_ENDPOINTS.RESET_PASSWORD,
  ];
  return publicAuthEndpoints.some(endpoint => error.config?.url?.endsWith(endpoint));
};

const shouldRetryWithRefresh = (error) => {
  return (
    error.response?.status === HTTP_STATUS.UNAUTHORIZED &&
    !error.config?._retry &&
    !isRefreshEndpointError(error) &&
    !isPublicAuthEndpoint(error)
  );
};

api.interceptors.request.use(
  addAuthorizationHeader,
  (error) => {
    console.error('Erro na requisição da API:', error);
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (isRefreshEndpointError(error)) {
      const authStore = useAuthStore();
      authStore.handleSessionExpired();
      return Promise.reject(error);
    }

    if (shouldRetryWithRefresh(error)) {
      const originalRequest = error.config;
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

export default api;