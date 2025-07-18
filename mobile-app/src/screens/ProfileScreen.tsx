import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { useAuth } from '../context/AuthContext';

const ProfileScreen: React.FC = () => {
  const { user, profile, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Abmelden',
      'Möchten Sie sich wirklich abmelden?',
      [
        {
          text: 'Abbrechen',
          style: 'cancel',
        },
        {
          text: 'Abmelden',
          style: 'destructive',
          onPress: logout,
        },
      ]
    );
  };

  const InfoCard: React.FC<{
    title: string;
    value: string;
    icon: string;
  }> = ({ title, value, icon }) => (
    <View style={styles.infoCard}>
      <Text style={styles.infoIcon}>{icon}</Text>
      <View style={styles.infoContent}>
        <Text style={styles.infoTitle}>{title}</Text>
        <Text style={styles.infoValue}>{value}</Text>
      </View>
    </View>
  );

  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.avatarContainer}>
          <Text style={styles.avatarText}>
            {user?.name?.charAt(0).toUpperCase() || 'U'}
          </Text>
        </View>
        <Text style={styles.userName}>{user?.name || 'Benutzer'}</Text>
        <Text style={styles.userEmail}>{user?.email || 'E-Mail nicht verfügbar'}</Text>
      </View>

      {/* User Info */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Benutzerinformationen</Text>
        
        <InfoCard
          title="Name"
          value={user?.name || 'Nicht verfügbar'}
          icon="👤"
        />
        
        <InfoCard
          title="E-Mail"
          value={user?.email || 'Nicht verfügbar'}
          icon="📧"
        />
        
        <InfoCard
          title="Rolle"
          value={user?.role || 'Nicht verfügbar'}
          icon="🔐"
        />
        
        {user?.department && (
          <InfoCard
            title="Abteilung"
            value={user.department}
            icon="🏢"
          />
        )}
        
        {user?.phone && (
          <InfoCard
            title="Telefon"
            value={user.phone}
            icon="📱"
          />
        )}
      </View>

      {/* Token Info */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>App-Token</Text>
        
        <InfoCard
          title="Token-Name"
          value={profile?.token?.name || 'Nicht verfügbar'}
          icon="🔑"
        />
        
        <InfoCard
          title="Erstellt am"
          value={profile?.token?.created_at ? 
            new Date(profile.token.created_at).toLocaleDateString('de-DE') : 
            'Nicht verfügbar'
          }
          icon="📅"
        />
        
        {profile?.token?.expires_at && (
          <InfoCard
            title="Läuft ab am"
            value={new Date(profile.token.expires_at).toLocaleDateString('de-DE')}
            icon="⏰"
          />
        )}
      </View>

      {/* Actions */}
      <View style={styles.section}>
        <TouchableOpacity
          style={styles.logoutButton}
          onPress={handleLogout}
        >
          <Text style={styles.logoutButtonText}>Abmelden</Text>
        </TouchableOpacity>
      </View>

      {/* App Info */}
      <View style={styles.footer}>
        <Text style={styles.footerText}>
          VoltMaster Mobile App
        </Text>
        <Text style={styles.footerText}>
          Version 1.0.0
        </Text>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  header: {
    alignItems: 'center',
    padding: 24,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  avatarContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#1976D2',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  userName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 4,
  },
  userEmail: {
    fontSize: 16,
    color: '#6B7280',
  },
  section: {
    marginTop: 24,
    paddingHorizontal: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 16,
  },
  infoCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  infoIcon: {
    fontSize: 24,
    marginRight: 16,
  },
  infoContent: {
    flex: 1,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
    marginBottom: 4,
  },
  infoValue: {
    fontSize: 16,
    color: '#1F2937',
  },
  logoutButton: {
    backgroundColor: '#EF4444',
    borderRadius: 8,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  logoutButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  footer: {
    alignItems: 'center',
    padding: 24,
    marginTop: 24,
  },
  footerText: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 4,
  },
});

export default ProfileScreen;
