// Task-Typen basierend auf Laravel Task Model
export interface Task {
  id: number;
  title: string;
  description: string | null;
  priority: TaskPriority;
  status: TaskStatus;
  due_date: string | null;
  due_time: string | null;
  labels: string[] | null;
  estimated_minutes: number | null;
  actual_minutes: number | null;
  task_type_id: number | null;
  customer_id: number | null;
  supplier_id: number | null;
  solar_plant_id: string | null;
  billing_id: number | null;
  milestone_id: number | null;
  assigned_to: number | null;
  owner_id: number | null;
  created_by: number | null;
  parent_task_id: number | null;
  completed_at: string | null;
  task_number: string;
  sort_order: number | null;
  created_at: string;
  updated_at: string;
  
  // Beziehungen
  assigned_user?: User;
  owner?: User;
  creator?: User;
  customer?: Customer;
  supplier?: Supplier;
  solar_plant?: SolarPlant;
  parent_task?: Task;
  subtasks?: Task[];
  
  // Berechnete Felder
  is_overdue: boolean;
  is_due_today: boolean;
  priority_color: string;
  status_color: string;
  progress_percentage: number;
}

export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';
export type TaskStatus = 'open' | 'in_progress' | 'waiting_external' | 'waiting_internal' | 'completed' | 'cancelled';

export interface TaskCreateRequest {
  title: string;
  description?: string;
  priority: TaskPriority;
  status: TaskStatus;
  due_date?: string;
  due_time?: string;
  labels?: string[];
  estimated_minutes?: number;
  task_type_id?: number;
  customer_id?: number;
  supplier_id?: number;
  solar_plant_id?: string;
  billing_id?: number;
  milestone_id?: number;
  assigned_to?: number;
  parent_task_id?: number;
}

export interface TaskUpdateRequest {
  title?: string;
  description?: string;
  priority?: TaskPriority;
  status?: TaskStatus;
  due_date?: string;
  due_time?: string;
  labels?: string[];
  estimated_minutes?: number;
  actual_minutes?: number;
  task_type_id?: number;
  customer_id?: number;
  supplier_id?: number;
  solar_plant_id?: string;
  billing_id?: number;
  milestone_id?: number;
  assigned_to?: number;
  parent_task_id?: number;
}

export interface TaskFilters {
  status?: TaskStatus;
  priority?: TaskPriority;
  assigned_to?: number;
  owner_id?: number;
  customer_id?: number;
  supplier_id?: number;
  solar_plant_id?: string;
  parent_task_id?: number;
  main_tasks_only?: boolean;
  search?: string;
  sort_by?: 'created_at' | 'due_date' | 'priority' | 'status' | 'title';
  sort_direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface TaskResponse {
  success: boolean;
  message?: string;
  data: Task;
}

export interface TaskListResponse {
  success: boolean;
  data: Task[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// User-Typen
export interface User {
  id: number;
  name: string;
  email: string;
  salutation?: string;
  name_abbreviation?: string;
  address_form: 'du' | 'sie';
  phone?: string;
  department?: string;
  role: 'admin' | 'manager' | 'user' | 'viewer';
  is_active: boolean;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

export interface UserBasic {
  id: number;
  name: string;
  email: string;
}

// Customer-Typen
export interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  customer_number?: string;
}

// Supplier-Typen
export interface Supplier {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  supplier_number?: string;
}

// SolarPlant-Typen
export interface SolarPlant {
  id: string; // UUID
  name: string;
  status: 'active' | 'inactive' | 'planning' | 'maintenance';
  is_active: boolean;
  power_kw?: number;
  location?: string;
  customer_id?: number;
}

// Dropdown-Optionen
export interface DropdownOptions {
  priorities: Array<{
    value: string;
    label: string;
  }>;
  statuses: Array<{
    value: string;
    label: string;
  }>;
  task_types: Array<{
    id: number;
    name: string;
  }>;
}

// App-Profil
export interface AppProfile {
  token: {
    id: number;
    name: string;
    permissions: string[];
    expires_at?: string;
    created_at: string;
  };
  user: User;
}

// API-Response-Typen
export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}
