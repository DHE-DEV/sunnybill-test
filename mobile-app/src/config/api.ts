import Constants from 'expo-constants';

// API-Konfiguration basierend auf .env Variablen
export const API_CONFIG = {
  BASE_URL: `${process.env.EXPO_PUBLIC_API_URL}/api`,
  TIMEOUT: 10000,
  RETRY_ATTEMPTS: 3,
};

// API-Endpunkte
export const API_ENDPOINTS = {
  // Authentifizierung
  PROFILE: '/app/profile',
  LOGOUT: '/app/logout',
  
  // Aufgaben
  TASKS: '/app/tasks',
  TASK_STATUS: (id: number) => `/app/tasks/${id}/status`,
  TASK_ASSIGN: (id: number) => `/app/tasks/${id}/assign`,
  TASK_TIME: (id: number) => `/app/tasks/${id}/time`,
  TASK_SUBTASKS: (id: number) => `/app/tasks/${id}/subtasks`,
  
  // Dropdown-Daten
  USERS: '/app/users',
  CUSTOMERS: '/app/customers',
  SUPPLIERS: '/app/suppliers',
  SOLAR_PLANTS: '/app/solar-plants',
  OPTIONS: '/app/options',
};

// HTTP-Status-Codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  VALIDATION_ERROR: 422,
  SERVER_ERROR: 500,
};
