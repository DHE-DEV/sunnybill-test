<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\Task;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectAppointment;

trait CreatesTestData
{
    /**
     * Create a test customer
     */
    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::factory()->create($attributes);
    }

    /**
     * Create a test supplier
     */
    protected function createSupplier(array $attributes = []): Supplier
    {
        return Supplier::factory()->create($attributes);
    }

    /**
     * Create a test solar plant
     */
    protected function createSolarPlant(array $attributes = []): SolarPlant
    {
        return SolarPlant::factory()->create($attributes);
    }

    /**
     * Create a test task
     */
    protected function createTask(array $attributes = []): Task
    {
        return Task::factory()->create($attributes);
    }

    /**
     * Create a test project
     */
    protected function createProject(array $attributes = []): Project
    {
        return Project::factory()->create($attributes);
    }

    /**
     * Create a test project milestone
     */
    protected function createProjectMilestone(Project $project = null, array $attributes = []): ProjectMilestone
    {
        if (!$project) {
            $project = $this->createProject();
        }

        return ProjectMilestone::factory()->create(array_merge([
            'project_id' => $project->id,
        ], $attributes));
    }

    /**
     * Create a test project appointment
     */
    protected function createProjectAppointment(Project $project = null, array $attributes = []): ProjectAppointment
    {
        if (!$project) {
            $project = $this->createProject();
        }

        return ProjectAppointment::factory()->create(array_merge([
            'project_id' => $project->id,
        ], $attributes));
    }

    /**
     * Create multiple test entities using method mapping
     */
    protected function createMultiple(string $entity, int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        $methodMap = [
            'customer' => 'createCustomer',
            'supplier' => 'createSupplier',
            'solarPlant' => 'createSolarPlant',
            'task' => 'createTask',
            'project' => 'createProject',
        ];

        $method = $methodMap[$entity] ?? null;
        
        if (!$method || !method_exists($this, $method)) {
            throw new \InvalidArgumentException("No creation method found for entity: {$entity}");
        }

        $entities = collect();
        
        for ($i = 0; $i < $count; $i++) {
            $entities->push($this->$method($attributes));
        }

        return $entities;
    }

    /**
     * Create a complete project setup with milestones and appointments
     */
    protected function createCompleteProject(array $projectAttributes = []): array
    {
        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();
        $solarPlant = $this->createSolarPlant();

        $project = $this->createProject(array_merge([
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'solar_plant_id' => $solarPlant->id,
        ], $projectAttributes));

        $milestones = collect();
        for ($i = 0; $i < 3; $i++) {
            $milestones->push($this->createProjectMilestone($project));
        }

        $appointments = collect();
        for ($i = 0; $i < 2; $i++) {
            $appointments->push($this->createProjectAppointment($project));
        }

        return [
            'project' => $project,
            'customer' => $customer,
            'supplier' => $supplier,
            'solar_plant' => $solarPlant,
            'milestones' => $milestones,
            'appointments' => $appointments,
        ];
    }
}