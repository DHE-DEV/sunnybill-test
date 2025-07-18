import React from 'react';
import { Text, View, ActivityIndicator } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import DashboardScreen from '../screens/DashboardScreen';
import TaskListScreen from '../screens/TaskListScreen';
import TaskCreateScreen from '../screens/TaskCreateScreen';
import ProfileScreen from '../screens/ProfileScreen';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

const TabNavigator: React.FC = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: '#E5E7EB',
          paddingBottom: 8,
          paddingTop: 8,
          height: 60,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '600',
        },
        tabBarActiveTintColor: '#1976D2',
        tabBarInactiveTintColor: '#6B7280',
        headerStyle: {
          backgroundColor: '#1976D2',
        },
        headerTintColor: '#FFFFFF',
        headerTitleStyle: {
          fontWeight: 'bold',
        },
      }}
    >
      <Tab.Screen
        name="Dashboard"
        component={DashboardScreen}
        options={{
          title: 'Dashboard',
          tabBarIcon: ({ color }) => <TabIcon name="🏠" color={color} />,
          headerTitle: '🌞 VoltMaster Dashboard',
        }}
      />
      <Tab.Screen
        name="Tasks"
        component={TaskListScreen}
        options={{
          title: 'Aufgaben',
          tabBarIcon: ({ color }) => <TabIcon name="📋" color={color} />,
          headerTitle: 'Aufgaben',
        }}
      />
      <Tab.Screen
        name="Create"
        component={TaskCreateScreen}
        options={{
          title: 'Erstellen',
          tabBarIcon: ({ color }) => <TabIcon name="➕" color={color} />,
          headerTitle: 'Neue Aufgabe',
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          title: 'Profil',
          tabBarIcon: ({ color }) => <TabIcon name="👤" color={color} />,
          headerTitle: 'Profil',
        }}
      />
    </Tab.Navigator>
  );
};

const TabIcon: React.FC<{ name: string; color: string }> = ({ name, color }) => (
  <Text style={{ fontSize: 24, color }}>{name}</Text>
);

const AppNavigator: React.FC = () => {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={{
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: '#F9FAFB',
      }}>
        <ActivityIndicator size="large" color="#1976D2" />
        <Text style={{
          fontSize: 18,
          color: '#6B7280',
          marginTop: 16,
        }}>
          Lade App...
        </Text>
      </View>
    );
  }

  return (
    <NavigationContainer>
      {isAuthenticated ? (
        <TabNavigator />
      ) : (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
          <Stack.Screen name="Login" component={LoginScreen} />
        </Stack.Navigator>
      )}
    </NavigationContainer>
  );
};

export default AppNavigator;
