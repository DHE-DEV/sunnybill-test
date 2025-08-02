<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Project;

class ProjectApiTest extends TestCase
{
    use InteractsWithApi, CreatesTestData;

    public function test_can_list_projects()
    {
        $this->createMultiple('project', 5);

        $response = $this->apiGet('/projects', ['projects:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'customer_id',
                        'supplier_id',
                        'solar_plant_id',
                        'start_date',
                        'end_date',
                        'budget',
                        'progress_percentage',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);
    }

    public function test_can_create_project()
    {
        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();
        $solarPlant = $this->createSolarPlant();

        $projectData = [
            'name' => 'Solar Installation Project',
            'description' => 'Complete solar panel installation',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'solar_plant_id' => $solarPlant->id,
            'start_date' => '2024-03-01',
            'end_date' => '2024-06-30',
            'budget' => 50000.00,
            'progress_percentage' => 0,
        ];

        $response = $this->apiPost('/projects', $projectData, ['projects:create']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'customer_id',
                    'supplier_id',
                    'solar_plant_id',
                    'start_date',
                    'end_date',
                    'budget',
                    'progress_percentage'
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Solar Installation Project',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'solar_plant_id' => $solarPlant->id,
        ]);
    }

    public function test_can_show_project()
    {
        $project = $this->createProject();

        $response = $this->apiGet("/projects/{$project->id}", ['projects:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'customer_id',
                    'supplier_id',
                    'solar_plant_id',
                    'start_date',
                    'end_date',
                    'budget',
                    'progress_percentage',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                ]
            ]);
    }

    public function test_can_update_project()
    {
        $project = $this->createProject();
        
        $updateData = [
            'name' => 'Updated Project Name',
            'status' => 'in_progress',
            'progress_percentage' => 25,
            'budget' => 60000.00,
        ];

        $response = $this->apiPut("/projects/{$project->id}", $updateData, ['projects:update']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $project->id,
                    'name' => 'Updated Project Name',
                    'status' => 'in_progress',
                    'progress_percentage' => 25,
                    'budget' => 60000.00,
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'status' => 'in_progress',
            'progress_percentage' => 25,
            'budget' => 60000.00,
        ]);
    }

    public function test_can_delete_project()
    {
        $project = $this->createProject();

        $response = $this->apiDelete("/projects/{$project->id}", ['projects:delete']);

        $response->assertStatus(204);

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_can_update_project_status()
    {
        $project = $this->createProject(['status' => 'planning']);

        $response = $this->apiPatch("/projects/{$project->id}/status", [
            'status' => 'in_progress'
        ], ['projects:status']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $project->id,
                    'status' => 'in_progress',
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_can_get_project_progress()
    {
        $project = $this->createProject(['progress_percentage' => 45]);

        $response = $this->apiGet("/projects/{$project->id}/progress", ['projects:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'project_id',
                    'progress_percentage',
                    'milestones_completed',
                    'milestones_total',
                    'tasks_completed',
                    'tasks_total',
                    'last_updated'
                ]
            ]);
    }

    public function test_can_update_project_progress()
    {
        $project = $this->createProject(['progress_percentage' => 25]);

        $response = $this->apiPatch("/projects/{$project->id}/progress", [
            'progress_percentage' => 50
        ], ['projects:update']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $project->id,
                    'progress_percentage' => 50,
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'progress_percentage' => 50,
        ]);
    }

    public function test_can_get_project_milestones()
    {
        $project = $this->createProject();
        $milestone1 = $this->createProjectMilestone($project);
        $milestone2 = $this->createProjectMilestone($project);

        $response = $this->apiGet("/projects/{$project->id}/milestones", ['milestones:read']);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'name',
                        'description',
                        'due_date',
                        'status',
                        'progress_percentage'
                    ]
                ]
            ]);
    }

    public function test_can_create_project_milestone()
    {
        $project = $this->createProject();

        $milestoneData = [
            'name' => 'Design Phase Complete',
            'description' => 'Complete the design phase of the project',
            'due_date' => '2024-04-15',
            'status' => 'pending',
            'progress_percentage' => 0,
        ];

        $response = $this->apiPost("/projects/{$project->id}/milestones", $milestoneData, ['milestones:create']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'project_id',
                    'name',
                    'description',
                    'due_date',
                    'status',
                    'progress_percentage'
                ]
            ]);

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'Design Phase Complete',
            'status' => 'pending',
        ]);
    }

    public function test_can_get_project_appointments()
    {
        $project = $this->createProject();
        $appointment1 = $this->createProjectAppointment($project);
        $appointment2 = $this->createProjectAppointment($project);

        $response = $this->apiGet("/projects/{$project->id}/appointments", ['appointments:read']);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'title',
                        'description',
                        'start_date',
                        'end_date',
                        'location',
                        'attendees'
                    ]
                ]
            ]);
    }

    public function test_can_create_project_appointment()
    {
        $project = $this->createProject();

        $appointmentData = [
            'title' => 'Site Inspection',
            'description' => 'Initial site inspection for solar installation',
            'start_date' => '2024-03-15 09:00:00',
            'end_date' => '2024-03-15 11:00:00',
            'location' => 'Customer Site',
            'attendees' => ['John Doe', 'Jane Smith'],
        ];

        $response = $this->apiPost("/projects/{$project->id}/appointments", $appointmentData, ['appointments:create']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'project_id',
                    'title',
                    'description',
                    'start_date',
                    'end_date',
                    'location',
                    'attendees'
                ]
            ]);

        $this->assertDatabaseHas('project_appointments', [
            'project_id' => $project->id,
            'title' => 'Site Inspection',
            'location' => 'Customer Site',
        ]);
    }

    public function test_unauthorized_access_to_projects()
    {
        $this->assertUnauthorized('GET', '/projects');
        $this->assertUnauthorized('POST', '/projects', ['name' => 'Test']);
    }

    public function test_forbidden_access_with_wrong_permissions()
    {
        $project = $this->createProject();
        
        $this->assertForbidden('GET', '/projects', ['projects:read']);
        $this->assertForbidden('POST', '/projects', ['projects:create'], ['name' => 'Test']);
        $this->assertForbidden('PUT', "/projects/{$project->id}", ['projects:update'], ['name' => 'Updated']);
        $this->assertForbidden('DELETE', "/projects/{$project->id}", ['projects:delete']);
    }

    public function test_validation_errors_when_creating_project()
    {
        $response = $this->apiPost('/projects', [], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status', 'customer_id']);
    }

    public function test_project_not_found()
    {
        $response = $this->apiGet('/projects/999', ['projects:read']);
        
        $response->assertStatus(404);
    }

    public function test_project_progress_validation()
    {
        $project = $this->createProject();

        // Test invalid progress percentage
        $response = $this->apiPatch("/projects/{$project->id}/progress", [
            'progress_percentage' => 150  // Invalid: > 100
        ], ['projects:update']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_percentage']);
    }
}