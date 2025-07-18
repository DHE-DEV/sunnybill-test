import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import TaskService from '../services/TaskService';
import { Task } from '../types/Task';

interface DashboardStats {
  totalTasks: number;
  openTasks: number;
  inProgressTasks: number;
  overdueTasks: number;
  dueTodayTasks: number;
  myTasks: number;
  completedTasks: number;
}

const DashboardScreen: React.FC = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats>({
    totalTasks: 0,
    openTasks: 0,
    inProgressTasks: 0,
    overdueTasks: 0,
    dueTodayTasks: 0,
    myTasks: 0,
    completedTasks: 0,
  });
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setIsLoading(true);
      
      // Alle Aufgaben laden
      const allTasks = await TaskService.getTasks({ per_page: 1000 });
      const tasks = allTasks.data;
      
      // Statistiken berechnen
      const newStats: DashboardStats = {
        totalTasks: tasks.length,
        openTasks: tasks.filter(t => t.status === 'open').length,
        inProgressTasks: tasks.filter(t => t.status === 'in_progress').length,
        overdueTasks: tasks.filter(t => TaskService.isTaskOverdue(t)).length,
        dueTodayTasks: tasks.filter(t => TaskService.isTaskDueToday(t)).length,
        myTasks: tasks.filter(t => 
          t.assigned_to === user?.id || 
          t.owner_id === user?.id || 
          t.created_by === user?.id
        ).length,
        completedTasks: tasks.filter(t => t.status === 'completed').length,
      };
      
      setStats(newStats);
    } catch (error) {
      Alert.alert('Fehler', 'Dashboard-Daten konnten nicht geladen werden.');
    } finally {
      setIsLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadDashboardData();
    setRefreshing(false);
  };

  const StatCard: React.FC<{
    title: string;
    value: number;
    color: string;
    icon: string;
    onPress?: () => void;
  }> = ({ title, value, color, icon, onPress }) => (
    <TouchableOpacity
      style={[styles.statCard, { borderLeftColor: color }]}
      onPress={onPress}
      disabled={!onPress}
    >
      <View style={styles.statCardContent}>
        <View style={styles.statCardHeader}>
          <Text style={styles.statCardIcon}>{icon}</Text>
          <Text style={[styles.statCardValue, { color }]}>{value}</Text>
        </View>
        <Text style={styles.statCardTitle}>{title}</Text>
      </View>
    </TouchableOpacity>
  );

  const QuickActionButton: React.FC<{
    title: string;
    icon: string;
    color: string;
    onPress: () => void;
  }> = ({ title, icon, color, onPress }) => (
    <TouchableOpacity
      style={[styles.quickActionButton, { backgroundColor: color }]}
      onPress={onPress}
    >
      <Text style={styles.quickActionIcon}>{icon}</Text>
      <Text style={styles.quickActionTitle}>{title}</Text>
    </TouchableOpacity>
  );

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#1976D2" />
        <Text style={styles.loadingText}>Lade Dashboard...</Text>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
      }
    >
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.greeting}>
          Hallo, {user?.name || 'Benutzer'}!
        </Text>
        <Text style={styles.subtitle}>
          {new Date().toLocaleDateString('de-DE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
          })}
        </Text>
      </View>

      {/* Statistik-Karten */}
      <View style={styles.statsContainer}>
        <View style={styles.statsRow}>
          <StatCard
            title="Offene Aufgaben"
            value={stats.openTasks}
            color="#6B7280"
            icon="📋"
          />
          <StatCard
            title="In Bearbeitung"
            value={stats.inProgressTasks}
            color="#1976D2"
            icon="⚡"
          />
        </View>
        
        <View style={styles.statsRow}>
          <StatCard
            title="Überfällig"
            value={stats.overdueTasks}
            color="#EF4444"
            icon="⚠️"
          />
          <StatCard
            title="Heute fällig"
            value={stats.dueTodayTasks}
            color="#F59E0B"
            icon="🕐"
          />
        </View>
        
        <View style={styles.statsRow}>
          <StatCard
            title="Meine Aufgaben"
            value={stats.myTasks}
            color="#8B5CF6"
            icon="👤"
          />
          <StatCard
            title="Abgeschlossen"
            value={stats.completedTasks}
            color="#10B981"
            icon="✅"
          />
        </View>
      </View>

      {/* Quick Actions */}
      <View style={styles.quickActionsContainer}>
        <Text style={styles.sectionTitle}>Schnellzugriff</Text>
        <View style={styles.quickActionsGrid}>
          <QuickActionButton
            title="Neue Aufgabe"
            icon="➕"
            color="#1976D2"
            onPress={() => {/* Navigation zur Erstellung */}}
          />
          <QuickActionButton
            title="Meine Aufgaben"
            icon="👤"
            color="#8B5CF6"
            onPress={() => {/* Navigation zu gefilterten Aufgaben */}}
          />
          <QuickActionButton
            title="Überfällige"
            icon="⚠️"
            color="#EF4444"
            onPress={() => {/* Navigation zu überfälligen Aufgaben */}}
          />
          <QuickActionButton
            title="Heute fällig"
            icon="🕐"
            color="#F59E0B"
            onPress={() => {/* Navigation zu heute fälligen Aufgaben */}}
          />
        </View>
      </View>

      {/* Aktuelle Aktivitäten */}
      <View style={styles.activityContainer}>
        <Text style={styles.sectionTitle}>Übersicht</Text>
        <View style={styles.activityCard}>
          <Text style={styles.activityTitle}>Gesamt-Aufgaben</Text>
          <Text style={styles.activityValue}>{stats.totalTasks}</Text>
          <Text style={styles.activitySubtitle}>
            {stats.completedTasks} abgeschlossen, {stats.openTasks + stats.inProgressTasks} offen
          </Text>
        </View>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#6B7280',
  },
  header: {
    padding: 24,
    paddingTop: 16,
  },
  greeting: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 16,
    color: '#6B7280',
  },
  statsContainer: {
    paddingHorizontal: 24,
    marginBottom: 24,
  },
  statsRow: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginHorizontal: 6,
    borderLeftWidth: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  statCardContent: {
    alignItems: 'center',
  },
  statCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  statCardIcon: {
    fontSize: 24,
    marginRight: 8,
  },
  statCardValue: {
    fontSize: 24,
    fontWeight: 'bold',
  },
  statCardTitle: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
  },
  quickActionsContainer: {
    paddingHorizontal: 24,
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 16,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  quickActionButton: {
    width: '48%',
    borderRadius: 12,
    padding: 20,
    alignItems: 'center',
    marginBottom: 12,
  },
  quickActionIcon: {
    fontSize: 28,
    marginBottom: 8,
  },
  quickActionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
    textAlign: 'center',
  },
  activityContainer: {
    paddingHorizontal: 24,
    marginBottom: 24,
  },
  activityCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 20,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  activityTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 8,
  },
  activityValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1976D2',
    marginBottom: 8,
  },
  activitySubtitle: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
  },
});

export default DashboardScreen;
