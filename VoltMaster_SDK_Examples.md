# 🛠️ VoltMaster API SDK Beispiele

Diese Datei enthält fertige SDK-Implementierungen für verschiedene Programmiersprachen zur einfachen Integration der VoltMaster API.

## 📋 Inhaltsverzeichnis
- [JavaScript/TypeScript SDK](#javascripttypescript-sdk)
- [PHP SDK](#php-sdk)
- [Python SDK](#python-sdk)
- [C# SDK](#c-sdk)
- [Java SDK](#java-sdk)

---

## JavaScript/TypeScript SDK

### Installation
```bash
npm install axios
```

### VoltMasterAPI.js
```javascript
class VoltMasterAPI {
  constructor(apiToken, baseUrl = 'https://prosoltec.voltmaster.cloud') {
    this.apiToken = apiToken;
    this.baseUrl = baseUrl;
    this.headers = {
      'Authorization': `Bearer ${apiToken}`,
      'Content-Type': 'application/json'
    };
  }

  async request(endpoint, method = 'GET', data = null) {
    const url = `${this.baseUrl}${endpoint}`;
    const config = {
      method,
      headers: this.headers,
      ...(data && { body: JSON.stringify(data) })
    };

    const response = await fetch(url, config);
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(`API Error: ${response.status} - ${error.message || 'Unknown error'}`);
    }

    return await response.json();
  }

  // Authentication
  async getProfile() {
    return this.request('/api/app/profile');
  }

  async logout() {
    return this.request('/api/app/logout', 'POST');
  }

  // Tasks
  async getTasks(page = 1, perPage = 15) {
    return this.request(`/api/app/tasks?page=${page}&per_page=${perPage}`);
  }

  async getTask(taskId) {
    return this.request(`/api/app/tasks/${taskId}`);
  }

  async createTask(taskData) {
    return this.request('/api/app/tasks', 'POST', taskData);
  }

  async updateTask(taskId, taskData) {
    return this.request(`/api/app/tasks/${taskId}`, 'PUT', taskData);
  }

  async deleteTask(taskId) {
    return this.request(`/api/app/tasks/${taskId}`, 'DELETE');
  }

  async updateTaskStatus(taskId, status) {
    return this.request(`/api/app/tasks/${taskId}/status`, 'PATCH', { status });
  }

  async assignTask(taskId, userId) {
    return this.request(`/api/app/tasks/${taskId}/assign`, 'PATCH', { assigned_to: userId });
  }

  // Customers
  async getCustomers() {
    return this.request('/api/app/customers');
  }

  async getCustomer(customerId) {
    return this.request(`/api/app/customers/${customerId}`);
  }

  async createCustomer(customerData) {
    return this.request('/api/app/customers', 'POST', customerData);
  }

  async getCustomerProjects(customerId) {
    return this.request(`/api/app/customers/${customerId}/projects`);
  }

  async getCustomerTasks(customerId) {
    return this.request(`/api/app/customers/${customerId}/tasks`);
  }

  // Solar Plants
  async getSolarPlants() {
    return this.request('/api/app/solar-plants');
  }

  async getSolarPlant(plantId) {
    return this.request(`/api/app/solar-plants/${plantId}`);
  }

  async createSolarPlant(plantData) {
    return this.request('/api/app/solar-plants', 'POST', plantData);
  }

  async getSolarPlantComponents(plantId) {
    return this.request(`/api/app/solar-plants/${plantId}/components`);
  }

  async getSolarPlantStatistics(plantId) {
    return this.request(`/api/app/solar-plants/${plantId}/statistics`);
  }

  // Projects
  async getProjects() {
    return this.request('/api/app/projects');
  }

  async getProject(projectId) {
    return this.request(`/api/app/projects/${projectId}`);
  }

  async createProject(projectData) {
    return this.request('/api/app/projects', 'POST', projectData);
  }

  async getProjectProgress(projectId) {
    return this.request(`/api/app/projects/${projectId}/progress`);
  }

  // Suppliers
  async getSuppliers() {
    return this.request('/api/app/suppliers');
  }

  async getSupplier(supplierId) {
    return this.request(`/api/app/suppliers/${supplierId}`);
  }

  async createSupplier(supplierData) {
    return this.request('/api/app/suppliers', 'POST', supplierData);
  }

  // Costs
  async getCostsOverview() {
    return this.request('/api/app/costs/overview');
  }

  async getProjectCosts(projectId) {
    return this.request(`/api/app/projects/${projectId}/costs`);
  }

  async addProjectCost(projectId, costData) {
    return this.request(`/api/app/projects/${projectId}/costs`, 'POST', costData);
  }

  // Options
  async getUsers() {
    return this.request('/api/app/users');
  }

  async getTaskOptions() {
    return this.request('/api/app/options/tasks');
  }
}

// Verwendung
const api = new VoltMasterAPI('your-app-token-here');

// Beispiele
async function examples() {
  try {
    // Profil abrufen
    const profile = await api.getProfile();
    console.log('Profile:', profile);

    // Aufgaben abrufen
    const tasks = await api.getTasks();
    console.log('Tasks:', tasks);

    // Neue Aufgabe erstellen
    const newTask = await api.createTask({
      title: 'Test Task',
      description: 'Test Description',
      status: 'open',
      priority: 'medium'
    });
    console.log('New Task:', newTask);

  } catch (error) {
    console.error('API Error:', error.message);
  }
}

module.exports = VoltMasterAPI;
```

---

## PHP SDK

### VoltMasterAPI.php
```php
<?php

class VoltMasterAPI {
    private $apiToken;
    private $baseUrl;
    private $headers;

    public function __construct($apiToken, $baseUrl = 'https://prosoltec.voltmaster.cloud') {
        $this->apiToken = $apiToken;
        $this->baseUrl = $baseUrl;
        $this->headers = [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ];
    }

    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = $decodedResponse['message'] ?? 'Unknown error';
            throw new Exception("API Error: {$httpCode} - {$message}");
        }

        return $decodedResponse;
    }

    // Authentication
    public function getProfile() {
        return $this->request('/api/app/profile');
    }

    public function logout() {
        return $this->request('/api/app/logout', 'POST');
    }

    // Tasks
    public function getTasks($page = 1, $perPage = 15) {
        return $this->request("/api/app/tasks?page={$page}&per_page={$perPage}");
    }

    public function getTask($taskId) {
        return $this->request("/api/app/tasks/{$taskId}");
    }

    public function createTask($taskData) {
        return $this->request('/api/app/tasks', 'POST', $taskData);
    }

    public function updateTask($taskId, $taskData) {
        return $this->request("/api/app/tasks/{$taskId}", 'PUT', $taskData);
    }

    public function deleteTask($taskId) {
        return $this->request("/api/app/tasks/{$taskId}", 'DELETE');
    }

    public function updateTaskStatus($taskId, $status) {
        return $this->request("/api/app/tasks/{$taskId}/status", 'PATCH', ['status' => $status]);
    }

    public function assignTask($taskId, $userId) {
        return $this->request("/api/app/tasks/{$taskId}/assign", 'PATCH', ['assigned_to' => $userId]);
    }

    // Customers
    public function getCustomers() {
        return $this->request('/api/app/customers');
    }

    public function getCustomer($customerId) {
        return $this->request("/api/app/customers/{$customerId}");
    }

    public function createCustomer($customerData) {
        return $this->request('/api/app/customers', 'POST', $customerData);
    }

    public function getCustomerProjects($customerId) {
        return $this->request("/api/app/customers/{$customerId}/projects");
    }

    // Solar Plants
    public function getSolarPlants() {
        return $this->request('/api/app/solar-plants');
    }

    public function getSolarPlant($plantId) {
        return $this->request("/api/app/solar-plants/{$plantId}");
    }

    public function createSolarPlant($plantData) {
        return $this->request('/api/app/solar-plants', 'POST', $plantData);
    }

    // Projects
    public function getProjects() {
        return $this->request('/api/app/projects');
    }

    public function getProject($projectId) {
        return $this->request("/api/app/projects/{$projectId}");
    }

    public function createProject($projectData) {
        return $this->request('/api/app/projects', 'POST', $projectData);
    }

    // Costs
    public function getCostsOverview() {
        return $this->request('/api/app/costs/overview');
    }

    public function getProjectCosts($projectId) {
        return $this->request("/api/app/projects/{$projectId}/costs");
    }

    public function addProjectCost($projectId, $costData) {
        return $this->request("/api/app/projects/{$projectId}/costs", 'POST', $costData);
    }
}

// Verwendung
try {
    $api = new VoltMasterAPI('your-app-token-here');
    
    // Profil abrufen
    $profile = $api->getProfile();
    echo "Profile: " . json_encode($profile) . "\n";
    
    // Aufgaben abrufen
    $tasks = $api->getTasks();
    echo "Tasks: " . json_encode($tasks) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```

---

## Python SDK

### Installation
```bash
pip install requests
```

### voltmaster_api.py
```python
import requests
import json
from typing import Optional, Dict, Any

class VoltMasterAPI:
    def __init__(self, api_token: str, base_url: str = 'https://prosoltec.voltmaster.cloud'):
        self.api_token = api_token
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {api_token}',
            'Content-Type': 'application/json'
        }
        self.session = requests.Session()
        self.session.headers.update(self.headers)

    def request(self, endpoint: str, method: str = 'GET', data: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = self.session.request(
                method=method,
                url=url,
                json=data,
                timeout=30
            )
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            raise Exception(f"API Error: {e}")

    # Authentication
    def get_profile(self) -> Dict[str, Any]:
        return self.request('/api/app/profile')

    def logout(self) -> Dict[str, Any]:
        return self.request('/api/app/logout', 'POST')

    # Tasks
    def get_tasks(self, page: int = 1, per_page: int = 15) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks?page={page}&per_page={per_page}')

    def get_task(self, task_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks/{task_id}')

    def create_task(self, task_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request('/api/app/tasks', 'POST', task_data)

    def update_task(self, task_id: int, task_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks/{task_id}', 'PUT', task_data)

    def delete_task(self, task_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks/{task_id}', 'DELETE')

    def update_task_status(self, task_id: int, status: str) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks/{task_id}/status', 'PATCH', {'status': status})

    def assign_task(self, task_id: int, user_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/tasks/{task_id}/assign', 'PATCH', {'assigned_to': user_id})

    # Customers
    def get_customers(self) -> Dict[str, Any]:
        return self.request('/api/app/customers')

    def get_customer(self, customer_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/customers/{customer_id}')

    def create_customer(self, customer_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request('/api/app/customers', 'POST', customer_data)

    def get_customer_projects(self, customer_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/customers/{customer_id}/projects')

    # Solar Plants
    def get_solar_plants(self) -> Dict[str, Any]:
        return self.request('/api/app/solar-plants')

    def get_solar_plant(self, plant_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/solar-plants/{plant_id}')

    def create_solar_plant(self, plant_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request('/api/app/solar-plants', 'POST', plant_data)

    def get_solar_plant_statistics(self, plant_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/solar-plants/{plant_id}/statistics')

    # Projects
    def get_projects(self) -> Dict[str, Any]:
        return self.request('/api/app/projects')

    def get_project(self, project_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/projects/{project_id}')

    def create_project(self, project_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request('/api/app/projects', 'POST', project_data)

    # Costs
    def get_costs_overview(self) -> Dict[str, Any]:
        return self.request('/api/app/costs/overview')

    def get_project_costs(self, project_id: int) -> Dict[str, Any]:
        return self.request(f'/api/app/projects/{project_id}/costs')

    def add_project_cost(self, project_id: int, cost_data: Dict[str, Any]) -> Dict[str, Any]:
        return self.request(f'/api/app/projects/{project_id}/costs', 'POST', cost_data)

# Verwendung
if __name__ == "__main__":
    api = VoltMasterAPI('your-app-token-here')
    
    try:
        # Profil abrufen
        profile = api.get_profile()
        print(f"Profile: {profile}")
        
        # Aufgaben abrufen
        tasks = api.get_tasks()
        print(f"Tasks: {tasks}")
        
        # Neue Aufgabe erstellen
        new_task = api.create_task({
            'title': 'Test Task',
            'description': 'Test Description',
            'status': 'open',
            'priority': 'medium'
        })
        print(f"New Task: {new_task}")
        
    except Exception as e:
        print(f"Error: {e}")
```

---

## C# SDK

### VoltMasterAPI.cs
```csharp
using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;

public class VoltMasterAPI
{
    private readonly HttpClient _httpClient;
    private readonly string _baseUrl;

    public VoltMasterAPI(string apiToken, string baseUrl = "https://prosoltec.voltmaster.cloud")
    {
        _baseUrl = baseUrl;
        _httpClient = new HttpClient();
        _httpClient.DefaultRequestHeaders.Add("Authorization", $"Bearer {apiToken}");
        _httpClient.DefaultRequestHeaders.Add("Accept", "application/json");
    }

    private async Task<T> RequestAsync<T>(string endpoint, HttpMethod method, object data = null)
    {
        var url = $"{_baseUrl}{endpoint}";
        var request = new HttpRequestMessage(method, url);

        if (data != null)
        {
            var json = JsonSerializer.Serialize(data);
            request.Content = new StringContent(json, Encoding.UTF8, "application/json");
        }

        var response = await _httpClient.SendAsync(request);
        var content = await response.Content.ReadAsStringAsync();

        if (!response.IsSuccessStatusCode)
        {
            throw new Exception($"API Error: {response.StatusCode} - {content}");
        }

        return JsonSerializer.Deserialize<T>(content, new JsonSerializerOptions
        {
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase
        });
    }

    // Authentication
    public async Task<object> GetProfileAsync()
    {
        return await RequestAsync<object>("/api/app/profile", HttpMethod.Get);
    }

    public async Task<object> LogoutAsync()
    {
        return await RequestAsync<object>("/api/app/logout", HttpMethod.Post);
    }

    // Tasks
    public async Task<object> GetTasksAsync(int page = 1, int perPage = 15)
    {
        return await RequestAsync<object>($"/api/app/tasks?page={page}&per_page={perPage}", HttpMethod.Get);
    }

    public async Task<object> GetTaskAsync(int taskId)
    {
        return await RequestAsync<object>($"/api/app/tasks/{taskId}", HttpMethod.Get);
    }

    public async Task<object> CreateTaskAsync(object taskData)
    {
        return await RequestAsync<object>("/api/app/tasks", HttpMethod.Post, taskData);
    }

    public async Task<object> UpdateTaskAsync(int taskId, object taskData)
    {
        return await RequestAsync<object>($"/api/app/tasks/{taskId}", HttpMethod.Put, taskData);
    }

    public async Task<object> DeleteTaskAsync(int taskId)
    {
        return await RequestAsync<object>($"/api/app/tasks/{taskId}", HttpMethod.Delete);
    }

    public async Task<object> UpdateTaskStatusAsync(int taskId, string status)
    {
        return await RequestAsync<object>($"/api/app/tasks/{taskId}/status", 
            new HttpMethod("PATCH"), new { status });
    }

    // Customers
    public async Task<object> GetCustomersAsync()
    {
        return await RequestAsync<object>("/api/app/customers", HttpMethod.Get);
    }

    public async Task<object> GetCustomerAsync(int customerId)
    {
        return await RequestAsync<object>($"/api/app/customers/{customerId}", HttpMethod.Get);
    }

    public async Task<object> CreateCustomerAsync(object customerData)
    {
        return await RequestAsync<object>("/api/app/customers", HttpMethod.Post, customerData);
    }

    // Solar Plants
    public async Task<object> GetSolarPlantsAsync()
    {
        return await RequestAsync<object>("/api/app/solar-plants", HttpMethod.Get);
    }

    public async Task<object> GetSolarPlantAsync(int plantId)
    {
        return await RequestAsync<object>($"/api/app/solar-plants/{plantId}", HttpMethod.Get);
    }

    // Projects
    public async Task<object> GetProjectsAsync()
    {
        return await RequestAsync<object>("/api/app/projects", HttpMethod.Get);
    }

    public async Task<object> GetProjectAsync(int projectId)
    {
        return await RequestAsync<object>($"/api/app/projects/{projectId}", HttpMethod.Get);
    }

    public void Dispose()
    {
        _httpClient?.Dispose();
    }
}

// Verwendung
class Program
{
    static async Task Main(string[] args)
    {
        var api = new VoltMasterAPI("your-app-token-here");
        
        try
        {
            // Profil abrufen
            var profile = await api.GetProfileAsync();
            Console.WriteLine($"Profile: {JsonSerializer.Serialize(profile)}");
            
            // Aufgaben abrufen
            var tasks = await api.GetTasksAsync();
            Console.WriteLine($"Tasks: {JsonSerializer.Serialize(tasks)}");
            
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error: {ex.Message}");
        }
        finally
        {
            api.Dispose();
        }
    }
}
```

---

## Java SDK

### VoltMasterAPI.java
```java
import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.JsonNode;

public class VoltMasterAPI {
    private final HttpClient httpClient;
    private final String baseUrl;
    private final String apiToken;
    private final ObjectMapper objectMapper;

    public VoltMasterAPI(String apiToken, String baseUrl) {
        this.apiToken = apiToken;
        this.baseUrl = baseUrl != null ? baseUrl : "https://prosoltec.voltmaster.cloud";
        this.httpClient = HttpClient.newBuilder()
            .connectTimeout(Duration.ofSeconds(30))
            .build();
        this.objectMapper = new ObjectMapper();
    }

    public VoltMasterAPI(String apiToken) {
        this(apiToken, null);
    }

    private JsonNode request(String endpoint, String method, Object data) throws IOException, InterruptedException {
        String url = baseUrl + endpoint;
        
        HttpRequest.Builder requestBuilder = HttpRequest.newBuilder()
            .uri(URI.create(url))
            .header("Authorization", "Bearer " + apiToken)
            .header("Content-Type", "application/json")
            .timeout(Duration.ofSeconds(30));

        switch (method.toUpperCase()) {
            case "GET":
                requestBuilder.GET();
                break;
            case "POST":
                String postBody = data != null ? objectMapper.writeValueAsString(data) : "";
                requestBuilder.POST(HttpRequest.BodyPublishers.ofString(postBody));
                break;
            case "PUT":
                String putBody = data != null ? objectMapper.writeValueAsString(data) : "";
                requestBuilder.PUT(HttpRequest.BodyPublishers.ofString(putBody));
                break;
            case "DELETE":
                requestBuilder.DELETE();
                break;
            case "PATCH":
                String patchBody = data != null ? objectMapper.writeValueAsString(data) : "";
                requestBuilder.method("PATCH", HttpRequest.BodyPublishers.ofString(patchBody));
                break;
        }

        HttpRequest request = requestBuilder.build();
        HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() >= 400) {
            throw new RuntimeException("API Error: " + response.statusCode() + " - " + response.body());
        }

        return objectMapper.readTree(response.body());
    }

    // Authentication
    public JsonNode getProfile() throws IOException, InterruptedException {
        return request("/api/app/profile", "GET", null);
    }

    public JsonNode logout() throws IOException, InterruptedException {
        return request("/api/app/logout", "POST", null);
    }

    // Tasks
    public JsonNode getTasks(int page, int perPage) throws IOException, InterruptedException {
        return request("/api/app/tasks?page=" + page + "&per_page=" + perPage, "GET", null);
    }

    public JsonNode getTasks() throws IOException, InterruptedException {
        return getTasks(1, 15);
    }

    public JsonNode getTask(int taskId) throws IOException, InterruptedException {
        return request("/api/app/tasks/" + taskId, "GET", null);
    }

    public JsonNode createTask(Object taskData) throws IOException, InterruptedException {
        return request("/api/app/tasks", "POST", taskData);
    }

    public JsonNode updateTask(int taskId, Object taskData) throws IOException, InterruptedException {
        return request("/api/app/tasks/" + taskId, "PUT", taskData);
    }

    public JsonNode deleteTask(int taskId) throws IOException, InterruptedException {
        return request("/api/app/tasks/" + taskId, "DELETE", null);
    }

    public JsonNode updateTaskStatus(int taskId, String status) throws IOException, InterruptedException {
        return request("/api/app/tasks/" + taskId + "/status", "PATCH", 
            objectMapper.createObjectNode().put("status", status));
    }

    // Customers
    public JsonNode getCustomers() throws IOException, InterruptedException {
        return request("/api/app/customers", "GET", null);
    }

    public JsonNode getCustomer(int customerId) throws IOException, InterruptedException {
        return request("/api/app/customers/" + customerId, "GET", null);
    }

    public JsonNode createCustomer(Object customerData) throws IOException, InterruptedException {
        return request("/api/app/customers", "POST", customerData);
    }

    // Solar Plants
    public JsonNode getSolarPlants() throws IOException, InterruptedException {
        return request("/api/app/solar-plants", "GET", null);
    }

    public JsonNode getSolarPlant(int plantId) throws IOException, InterruptedException {
        return request("/api/app/solar-plants/" + plantId, "GET", null);
    }

    // Projects
    public JsonNode getProjects() throws IOException, InterruptedException {
        return request("/api/app/projects", "GET", null);
    }

    public JsonNode getProject(int projectId) throws IOException, InterruptedException {
        return request("/api/app/projects/" + projectId, "GET", null);
    }

    // Costs
    public JsonNode getCostsOverview() throws IOException, InterruptedException {
        return request("/api/app/costs/overview", "GET", null);
    }

    public JsonNode getProjectCosts(int projectId) throws IOException, InterruptedException {
        return request("/api/app/projects/" + projectId + "/costs", "GET", null);
    }
}

// Verwendung
public class Main {
    public static void main(String[] args) {
        VoltMasterAPI api = new VoltMasterAPI("your-app-token-here");
        
        try {
            // Profil abrufen
            JsonNode profile = api.getProfile();
            System.out.println("Profile: " + profile.toString());
            
            // Aufgaben abrufen
            JsonNode tasks = api.getTasks();
            System.out.println("Tasks: " + tasks.toString());
            
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        }
    }
}
```

---

## 🚀 Schnellstart

1. **API Token erhalten**: Kontaktiere das VoltMaster Team für einen API Token
2. **SDK wählen**: Wähle das passende SDK für deine Programmiersprache
3. **Token einsetzen**: Ersetze `your-app-token-here` mit deinem echten Token
4. **Testen**: Starte mit einfachen Aufrufen wie `getProfile()` oder `getTasks()`

## 📞 Support

Bei Fragen zu den SDKs oder der API-Integration:
- **Email**: api@voltmaster.com
- **Dokumentation**: https://prosoltec.voltmaster.cloud/api/documentation