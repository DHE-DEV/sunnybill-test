import ApiService from './ApiService';
import { API_ENDPOINTS } from '../config/api';
import {
  Task,
  TaskCreateRequest,
  TaskUpdateRequest,
  TaskFilters,
  TaskResponse,
  TaskListResponse,
  AppProfile,
  UserBasic,
  Customer,
  Supplier,
  SolarPlant,
  DropdownOptions,
  TaskStatus,
  ApiResponse,
} from '../types/Task';

class TaskService {
  // Authentifizierung
  async getProfile(): Promise<AppProfile> {
    const response = await ApiService.get<AppProfile>(API_ENDPOINTS.PROFILE);
    if (!response.success || !response.data) {
      throw new Error('Profil konnte nicht geladen werden');
    }
    return response.data;
  }

  async validateToken(): Promise<boolean> {
    try {
      await this.getProfile();
      return true;
    } catch {
      return false;
    }
  }

  // Aufgaben-CRUD
  async getTasks(filters: TaskFilters = {}): Promise<TaskListResponse> {
    const response = await ApiService.get<Task[]>(API_ENDPOINTS.TASKS, filters);
    if (!response.success) {
      throw new Error('Aufgaben konnten nicht geladen werden');
    }
    
    return {
      success: true,
      data: response.data || [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: filters.per_page || 15,
        total: response.data?.length || 0,
      },
    };
  }

  async getTask(id: number): Promise<Task> {
    const response = await ApiService.get<Task>(`${API_ENDPOINTS.TASKS}/${id}`);
    if (!response.success || !response.data) {
      throw new Error('Aufgabe konnte nicht geladen werden');
    }
    return response.data;
  }

  async createTask(task: TaskCreateRequest): Promise<Task> {
    const response = await ApiService.post<Task>(API_ENDPOINTS.TASKS, task);
    if (!response.success || !response.data) {
      throw new Error('Aufgabe konnte nicht erstellt werden');
    }
    return response.data;
  }

  async updateTask(id: number, task: TaskUpdateRequest): Promise<Task> {
    const response = await ApiService.put<Task>(`${API_ENDPOINTS.TASKS}/${id}`, task);
    if (!response.success || !response.data) {
      throw new Error('Aufgabe konnte nicht aktualisiert werden');
    }
    return response.data;
  }

  async deleteTask(id: number): Promise<void> {
    const response = await ApiService.delete(`${API_ENDPOINTS.TASKS}/${id}`);
    if (!response.success) {
      throw new Error('Aufgabe konnte nicht gelöscht werden');
    }
  }

  // Spezielle Aktionen
  async updateTaskStatus(id: number, status: TaskStatus): Promise<Task> {
    const response = await ApiService.patch<Task>(
      API_ENDPOINTS.TASK_STATUS(id),
      { status }
    );
    if (!response.success || !response.data) {
      throw new Error('Status konnte nicht geändert werden');
    }
    return response.data;
  }

  async assignTask(id: number, userId: number): Promise<Task> {
    const response = await ApiService.patch<Task>(
      API_ENDPOINTS.TASK_ASSIGN(id),
      { assigned_to: userId }
    );
    if (!response.success || !response.data) {
      throw new Error('Aufgabe konnte nicht zugewiesen werden');
    }
    return response.data;
  }

  async updateTaskTime(
    id: number,
    data: { estimated_minutes?: number; actual_minutes?: number }
  ): Promise<Task> {
    const response = await ApiService.patch<Task>(
      API_ENDPOINTS.TASK_TIME(id),
      data
    );
    if (!response.success || !response.data) {
      throw new Error('Zeiten konnten nicht aktualisiert werden');
    }
    return response.data;
  }

  async getSubtasks(id: number): Promise<Task[]> {
    const response = await ApiService.get<Task[]>(API_ENDPOINTS.TASK_SUBTASKS(id));
    if (!response.success) {
      throw new Error('Unteraufgaben konnten nicht geladen werden');
    }
    return response.data || [];
  }

  // Dropdown-Daten
  async getUsers(): Promise<UserBasic[]> {
    const response = await ApiService.get<UserBasic[]>(API_ENDPOINTS.USERS);
    if (!response.success) {
      throw new Error('Benutzer konnten nicht geladen werden');
    }
    return response.data || [];
  }

  async getCustomers(): Promise<Customer[]> {
    const response = await ApiService.get<Customer[]>(API_ENDPOINTS.CUSTOMERS);
    if (!response.success) {
      throw new Error('Kunden konnten nicht geladen werden');
    }
    return response.data || [];
  }

  async getSuppliers(): Promise<Supplier[]> {
    const response = await ApiService.get<Supplier[]>(API_ENDPOINTS.SUPPLIERS);
    if (!response.success) {
      throw new Error('Lieferanten konnten nicht geladen werden');
    }
    return response.data || [];
  }

  async getSolarPlants(): Promise<SolarPlant[]> {
    const response = await ApiService.get<SolarPlant[]>(API_ENDPOINTS.SOLAR_PLANTS);
    if (!response.success) {
      throw new Error('Solaranlagen konnten nicht geladen werden');
    }
    return response.data || [];
  }

  async getOptions(): Promise<DropdownOptions> {
    const response = await ApiService.get<DropdownOptions>(API_ENDPOINTS.OPTIONS);
    if (!response.success || !response.data) {
      throw new Error('Optionen konnten nicht geladen werden');
    }
    return response.data;
  }

  // Bulk-Operationen
  async updateMultipleTaskStatus(taskIds: number[], status: TaskStatus): Promise<void> {
    const promises = taskIds.map(id => this.updateTaskStatus(id, status));
    await Promise.all(promises);
  }

  async assignMultipleTasks(taskIds: number[], userId: number): Promise<void> {
    const promises = taskIds.map(id => this.assignTask(id, userId));
    await Promise.all(promises);
  }

  async deleteMultipleTasks(taskIds: number[]): Promise<void> {
    const promises = taskIds.map(id => this.deleteTask(id));
    await Promise.all(promises);
  }

  // Utility-Methoden
  getTaskPriorityColor(priority: string): string {
    switch (priority) {
      case 'low': return '#6b7280';
      case 'medium': return '#2563eb';
      case 'high': return '#f59e0b';
      case 'urgent': return '#ef4444';
      default: return '#6b7280';
    }
  }

  getTaskStatusColor(status: string): string {
    switch (status) {
      case 'open': return '#6b7280';
      case 'in_progress': return '#2563eb';
      case 'waiting_external': return '#f59e0b';
      case 'waiting_internal': return '#8b5cf6';
      case 'completed': return '#10b981';
      case 'cancelled': return '#ef4444';
      default: return '#6b7280';
    }
  }

  getPriorityLabel(priority: string): string {
    switch (priority) {
      case 'low': return 'Niedrig';
      case 'medium': return 'Mittel';
      case 'high': return 'Hoch';
      case 'urgent': return 'Dringend';
      default: return priority;
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'open': return 'Offen';
      case 'in_progress': return 'In Bearbeitung';
      case 'waiting_external': return 'Warte auf Extern';
      case 'waiting_internal': return 'Warte auf Intern';
      case 'completed': return 'Abgeschlossen';
      case 'cancelled': return 'Abgebrochen';
      default: return status;
    }
  }

  isTaskOverdue(task: Task): boolean {
    if (!task.due_date) return false;
    const dueDate = new Date(task.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return dueDate < today && task.status !== 'completed';
  }

  isTaskDueToday(task: Task): boolean {
    if (!task.due_date) return false;
    const dueDate = new Date(task.due_date);
    const today = new Date();
    return dueDate.toDateString() === today.toDateString();
  }

  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  }

  formatDateTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }
}

export default new TaskService();
