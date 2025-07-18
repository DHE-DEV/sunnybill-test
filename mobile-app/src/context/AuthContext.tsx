import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import ApiService from '../services/ApiService';
import TaskService from '../services/TaskService';
import { AppProfile, User } from '../types/Task';

interface AuthContextType {
  isAuthenticated: boolean;
  isLoading: boolean;
  user: User | null;
  profile: AppProfile | null;
  login: (token: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshProfile: () => Promise<void>;
  invalidateUserCache: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [user, setUser] = useState<User | null>(null);
  const [profile, setProfile] = useState<AppProfile | null>(null);

  // Initialisierung beim App-Start
  useEffect(() => {
    checkAuthStatus();
  }, []);

  // Hilfsfunktionen für lokale Speicherung
  const storeUserData = async (userData: AppProfile) => {
    try {
      const userDataString = JSON.stringify(userData);
      const timestamp = new Date().getTime().toString();
      
      if (Platform.OS === 'web') {
        localStorage.setItem('user_data', userDataString);
        localStorage.setItem('user_data_timestamp', timestamp);
      } else {
        await SecureStore.setItemAsync('user_data', userDataString);
        await SecureStore.setItemAsync('user_data_timestamp', timestamp);
      }
    } catch (error) {
      console.error('Failed to store user data:', error);
    }
  };

  const getStoredUserData = async (): Promise<{ data: AppProfile | null; timestamp: number }> => {
    try {
      let userDataString: string | null = null;
      let timestampString: string | null = null;
      
      if (Platform.OS === 'web') {
        userDataString = localStorage.getItem('user_data');
        timestampString = localStorage.getItem('user_data_timestamp');
      } else {
        userDataString = await SecureStore.getItemAsync('user_data');
        timestampString = await SecureStore.getItemAsync('user_data_timestamp');
      }
      
      if (userDataString && timestampString) {
        const userData = JSON.parse(userDataString) as AppProfile;
        const timestamp = parseInt(timestampString);
        return { data: userData, timestamp };
      }
    } catch (error) {
      console.error('Failed to get stored user data:', error);
    }
    
    return { data: null, timestamp: 0 };
  };

  const clearStoredUserData = async () => {
    try {
      if (Platform.OS === 'web') {
        localStorage.removeItem('user_data');
        localStorage.removeItem('user_data_timestamp');
      } else {
        await SecureStore.deleteItemAsync('user_data');
        await SecureStore.deleteItemAsync('user_data_timestamp');
      }
    } catch (error) {
      console.error('Failed to clear stored user data:', error);
    }
  };

  const isDataStale = (timestamp: number, maxAgeMinutes: number = 30): boolean => {
    const now = new Date().getTime();
    const maxAge = maxAgeMinutes * 60 * 1000; // Konvertiere Minuten zu Millisekunden
    return (now - timestamp) > maxAge;
  };

  const checkAuthStatus = async () => {
    try {
      setIsLoading(true);
      
      let token: string | null = null;
      
      // 1. Zuerst prüfen, ob Token in .env Datei vorhanden ist
      const envToken = process.env.APP_TOKEN;
      if (envToken) {
        console.log('Using token from .env file');
        token = envToken;
      } else {
        // 2. Falls kein ENV-Token, gespeicherten Token verwenden
        if (Platform.OS === 'web') {
          // Web: LocalStorage verwenden
          token = localStorage.getItem('app_token');
        } else {
          // Mobile: SecureStore verwenden
          token = await SecureStore.getItemAsync('app_token');
        }
      }
      
      if (token) {
        await ApiService.setToken(token);
        
        // 3. Prüfen, ob lokale Benutzerdaten vorhanden und aktuell sind
        const { data: cachedUserData, timestamp } = await getStoredUserData();
        
        if (cachedUserData && !isDataStale(timestamp)) {
          // Verwende lokale Daten
          console.log('Using cached user data');
          setProfile(cachedUserData);
          setUser(cachedUserData.user);
          setIsAuthenticated(true);
          
          // Token trotzdem validieren (aber nicht Profil laden)
          const isValid = await TaskService.validateToken();
          if (!isValid) {
            await logout();
            return;
          }
        } else {
          // Daten sind veraltet oder nicht vorhanden - vom Server laden
          console.log('Loading fresh user data from server');
          
          // Token validieren
          const isValid = await TaskService.validateToken();
          
          if (isValid) {
            const profileData = await TaskService.getProfile();
            setProfile(profileData);
            setUser(profileData.user);
            setIsAuthenticated(true);
            
            // Neue Daten lokal speichern
            await storeUserData(profileData);
          } else {
            // Token ist ungültig - ausloggen
            await logout();
          }
        }
        
        // Wenn ENV-Token verwendet wird, auch lokal speichern für Konsistenz
        if (envToken) {
          if (Platform.OS === 'web') {
            localStorage.setItem('app_token', token);
          } else {
            await SecureStore.setItemAsync('app_token', token);
          }
        }
      } else {
        // Kein Token vorhanden - nicht eingeloggt
        setIsAuthenticated(false);
        setProfile(null);
        setUser(null);
        await clearStoredUserData();
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      await logout();
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (token: string) => {
    try {
      setIsLoading(true);
      
      // Token setzen
      await ApiService.setToken(token);
      
      // Profil laden
      const profileData = await TaskService.getProfile();
      
      setProfile(profileData);
      setUser(profileData.user);
      setIsAuthenticated(true);
      
      // Benutzerdaten lokal speichern
      await storeUserData(profileData);
    } catch (error) {
      console.error('Login failed:', error);
      await logout();
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      await ApiService.logout();
      
      setProfile(null);
      setUser(null);
      setIsAuthenticated(false);
      
      // Lokal gespeicherte Benutzerdaten löschen
      await clearStoredUserData();
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  const refreshProfile = async () => {
    try {
      if (isAuthenticated) {
        const profileData = await TaskService.getProfile();
        setProfile(profileData);
        setUser(profileData.user);
        
        // Aktualisierte Daten lokal speichern
        await storeUserData(profileData);
      }
    } catch (error) {
      console.error('Profile refresh failed:', error);
      await logout();
    }
  };

  const invalidateUserCache = async () => {
    try {
      console.log('Invalidating user cache - forcing fresh data load');
      await clearStoredUserData();
      
      if (isAuthenticated) {
        // Profil neu laden
        await refreshProfile();
      }
    } catch (error) {
      console.error('Cache invalidation failed:', error);
    }
  };

  const value: AuthContextType = {
    isAuthenticated,
    isLoading,
    user,
    profile,
    login,
    logout,
    refreshProfile,
    invalidateUserCache,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
