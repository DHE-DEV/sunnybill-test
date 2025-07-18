import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from 'react-native';
import TaskService from '../services/TaskService';
import { TaskCreateRequest, TaskPriority, TaskStatus } from '../types/Task';

const TaskCreateScreen: React.FC = () => {
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState<TaskCreateRequest>({
    title: '',
    description: '',
    priority: 'medium',
    status: 'open',
  });

  const handleSubmit = async () => {
    if (!formData.title.trim()) {
      Alert.alert('Fehler', 'Bitte geben Sie einen Titel ein.');
      return;
    }

    setIsLoading(true);
    try {
      await TaskService.createTask(formData);
      Alert.alert('Erfolg', 'Aufgabe wurde erfolgreich erstellt!');
      
      // Formular zurücksetzen
      setFormData({
        title: '',
        description: '',
        priority: 'medium',
        status: 'open',
      });
    } catch (error) {
      Alert.alert('Fehler', 'Aufgabe konnte nicht erstellt werden.');
    } finally {
      setIsLoading(false);
    }
  };

  const PriorityButton: React.FC<{
    priority: TaskPriority;
    label: string;
    color: string;
  }> = ({ priority, label, color }) => (
    <TouchableOpacity
      style={[
        styles.priorityButton,
        { backgroundColor: formData.priority === priority ? color : '#F3F4F6' },
      ]}
      onPress={() => setFormData({ ...formData, priority })}
    >
      <Text
        style={[
          styles.priorityButtonText,
          { color: formData.priority === priority ? '#FFFFFF' : '#374151' },
        ]}
      >
        {label}
      </Text>
    </TouchableOpacity>
  );

  return (
    <ScrollView style={styles.container}>
      <View style={styles.form}>
        {/* Titel */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Titel *</Text>
          <TextInput
            style={styles.input}
            placeholder="Aufgabentitel eingeben"
            value={formData.title}
            onChangeText={(text) => setFormData({ ...formData, title: text })}
            editable={!isLoading}
          />
        </View>

        {/* Beschreibung */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Beschreibung</Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            placeholder="Beschreibung eingeben (optional)"
            value={formData.description}
            onChangeText={(text) => setFormData({ ...formData, description: text })}
            multiline
            numberOfLines={4}
            editable={!isLoading}
          />
        </View>

        {/* Priorität */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Priorität</Text>
          <View style={styles.priorityContainer}>
            <PriorityButton
              priority="low"
              label="Niedrig"
              color="#6B7280"
            />
            <PriorityButton
              priority="medium"
              label="Mittel"
              color="#2563EB"
            />
            <PriorityButton
              priority="high"
              label="Hoch"
              color="#F59E0B"
            />
            <PriorityButton
              priority="urgent"
              label="Dringend"
              color="#EF4444"
            />
          </View>
        </View>

        {/* Submit Button */}
        <TouchableOpacity
          style={[styles.submitButton, isLoading && styles.submitButtonDisabled]}
          onPress={handleSubmit}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.submitButtonText}>Aufgabe erstellen</Text>
          )}
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  form: {
    padding: 24,
  },
  inputGroup: {
    marginBottom: 24,
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 16,
    color: '#1F2937',
    minHeight: 48,
  },
  textArea: {
    height: 120,
    textAlignVertical: 'top',
  },
  priorityContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  priorityButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    marginRight: 8,
    marginBottom: 8,
  },
  priorityButtonText: {
    fontSize: 14,
    fontWeight: '600',
  },
  submitButton: {
    backgroundColor: '#1976D2',
    borderRadius: 8,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 24,
  },
  submitButtonDisabled: {
    backgroundColor: '#9CA3AF',
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
});

export default TaskCreateScreen;
