import axios, { AxiosInstance, AxiosResponse } from 'axios';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import { API_CONFIG, API_ENDPOINTS, HTTP_STATUS } from '../config/api';
import { ApiResponse, ApiError } from '../types/Task';

class ApiService {
  private api: AxiosInstance;
  private token: string | null = null;

  constructor() {
    this.api = axios.create({
      baseURL: API_CONFIG.BASE_URL,
      timeout: API_CONFIG.TIMEOUT,
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'Accept': 'application/json',
      },
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request Interceptor - Token hinzufügen
    this.api.interceptors.request.use(
      async (config) => {
        if (!this.token) {
          if (Platform.OS === 'web') {
            // Web: LocalStorage verwenden
            this.token = localStorage.getItem('app_token');
          } else {
            // Mobile: SecureStore verwenden
            this.token = await SecureStore.getItemAsync('app_token');
          }
        }
        
        if (this.token) {
          // Token direkt verwenden
          config.headers.Authorization = `Bearer ${this.token}`;
        }
        
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response Interceptor - Error Handling
    this.api.interceptors.response.use(
      (response: AxiosResponse) => response,
      async (error) => {
        if (error.response?.status === HTTP_STATUS.UNAUTHORIZED) {
          // Token ist ungültig - Nur lokales Token löschen
          await this.clearLocalToken();
        }
        
        return Promise.reject(this.handleError(error));
      }
    );
  }

  private handleError(error: any): ApiError {
    if (error.response) {
      // Server hat mit Error-Status geantwortet
      return {
        success: false,
        message: error.response.data?.message || 'Ein Serverfehler ist aufgetreten',
        errors: error.response.data?.errors || undefined,
      };
    } else if (error.request) {
      // Keine Antwort vom Server
      return {
        success: false,
        message: 'Keine Verbindung zum Server. Prüfen Sie Ihre Internetverbindung.',
      };
    } else {
      // Anderer Fehler
      return {
        success: false,
        message: error.message || 'Ein unbekannter Fehler ist aufgetreten',
      };
    }
  }

  // Token-Management
  async setToken(token: string): Promise<void> {
    this.token = token;
    
    if (Platform.OS === 'web') {
      // Web: LocalStorage verwenden
      localStorage.setItem('app_token', token);
    } else {
      // Mobile: SecureStore verwenden
      await SecureStore.setItemAsync('app_token', token);
    }
  }

  async getToken(): Promise<string | null> {
    if (!this.token) {
      if (Platform.OS === 'web') {
        // Web: LocalStorage verwenden
        this.token = localStorage.getItem('app_token');
      } else {
        // Mobile: SecureStore verwenden
        this.token = await SecureStore.getItemAsync('app_token');
      }
    }
    return this.token;
  }

  // Nur lokales Token löschen (für Interceptor)
  private async clearLocalToken(): Promise<void> {
    this.token = null;
    
    if (Platform.OS === 'web') {
      // Web: LocalStorage verwenden
      localStorage.removeItem('app_token');
    } else {
      // Mobile: SecureStore verwenden
      await SecureStore.deleteItemAsync('app_token');
    }
  }

  // Vollständiger Logout mit Server-Request
  async logout(): Promise<void> {
    try {
      // Logout-Request an den Server senden
      await this.post(API_ENDPOINTS.LOGOUT);
    } catch (error) {
      // Auch bei Server-Fehlern lokales Token löschen
      console.warn('Server logout failed, clearing local token anyway:', error);
    } finally {
      // Lokales Token löschen
      await this.clearLocalToken();
    }
  }

  // HTTP-Methoden
  async get<T>(url: string, params?: any): Promise<ApiResponse<T>> {
    const response = await this.api.get(url, { params });
    return response.data;
  }

  async post<T>(url: string, data?: any): Promise<ApiResponse<T>> {
    const response = await this.api.post(url, data);
    return response.data;
  }

  async put<T>(url: string, data?: any): Promise<ApiResponse<T>> {
    const response = await this.api.put(url, data);
    return response.data;
  }

  async patch<T>(url: string, data?: any): Promise<ApiResponse<T>> {
    const response = await this.api.patch(url, data);
    return response.data;
  }

  async delete<T>(url: string): Promise<ApiResponse<T>> {
    const response = await this.api.delete(url);
    return response.data;
  }

  // Retry-Logik
  async retryRequest<T>(
    requestFn: () => Promise<ApiResponse<T>>,
    maxRetries = API_CONFIG.RETRY_ATTEMPTS
  ): Promise<ApiResponse<T>> {
    let lastError: any;
    
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        return await requestFn();
      } catch (error) {
        lastError = error;
        
        if (attempt < maxRetries) {
          // Exponential backoff
          await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
        }
      }
    }
    
    throw lastError;
  }

  // Netzwerk-Status prüfen
  async checkConnectivity(): Promise<boolean> {
    try {
      const response = await this.api.get('/ping', { timeout: 5000 });
      return response.status === HTTP_STATUS.OK;
    } catch {
      return false;
    }
  }
}

export default new ApiService();
