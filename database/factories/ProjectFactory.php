<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+12 months');
        
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']),
            'customer_id' => Customer::factory(),
            'supplier_id' => Supplier::factory(),
            'solar_plant_id' => SolarPlant::factory(),
            'project_manager_id' => User::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'budget' => $this->faker->randomFloat(2, 10000, 100000),
            'actual_costs' => $this->faker->optional(0.6)->randomFloat(2, 5000, 80000),
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'project_type' => $this->faker->randomElement(['installation', 'maintenance', 'expansion', 'consultation']),
            'expected_revenue' => $this->faker->optional(0.7)->randomFloat(2, 15000, 120000),
            'contract_signed_at' => $this->faker->optional(0.8)->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Project in planning phase
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
            'progress_percentage' => $this->faker->numberBetween(0, 25),
            'start_date' => $this->faker->dateTimeBetween('now', '+2 months'),
            'contract_signed_at' => null,
        ]);
    }

    /**
     * Project in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'progress_percentage' => $this->faker->numberBetween(25, 75),
            'start_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'contract_signed_at' => $this->faker->dateTimeBetween('-4 months', '-1 month'),
        ]);
    }

    /**
     * Completed project
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'start_date' => $this->faker->dateTimeBetween('-12 months', '-3 months'),
            'end_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'contract_signed_at' => $this->faker->dateTimeBetween('-13 months', '-6 months'),
            'completed_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ]);
    }

    /**
     * Project on hold
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
            'progress_percentage' => $this->faker->numberBetween(10, 60),
            'notes' => 'Project is currently on hold due to: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Cancelled project
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'progress_percentage' => $this->faker->numberBetween(0, 50),
            'cancelled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * High priority project
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'budget' => $this->faker->randomFloat(2, 50000, 200000),
        ]);
    }

    /**
     * Urgent project
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'budget' => $this->faker->randomFloat(2, 75000, 300000),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * Large budget project
     */
    public function largeBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget' => $this->faker->randomFloat(2, 100000, 500000),
            'expected_revenue' => $this->faker->randomFloat(2, 120000, 600000),
        ]);
    }

    /**
     * Small budget project
     */
    public function smallBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget' => $this->faker->randomFloat(2, 5000, 25000),
            'expected_revenue' => $this->faker->randomFloat(2, 6000, 30000),
        ]);
    }

    /**
     * Installation project type
     */
    public function installation(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_type' => 'installation',
            'name' => 'Solar Installation - ' . $this->faker->city(),
        ]);
    }

    /**
     * Maintenance project type
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_type' => 'maintenance',
            'name' => 'Maintenance - ' . $this->faker->company(),
            'budget' => $this->faker->randomFloat(2, 2000, 15000),
        ]);
    }

    /**
     * Expansion project type
     */
    public function expansion(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_type' => 'expansion',
            'name' => 'Solar Plant Expansion - ' . $this->faker->city(),
        ]);
    }

    /**
     * Overdue project (past end date but not completed)
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement(['in_progress', 'on_hold']),
            'end_date' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
            'progress_percentage' => $this->faker->numberBetween(30, 80),
        ]);
    }

    /**
     * Project with contract signed
     */
    public function withContract(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_signed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'contract_value' => $this->faker->randomFloat(2, 20000, 150000),
        ]);
    }

    /**
     * Project without contract
     */
    public function withoutContract(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_signed_at' => null,
            'contract_value' => null,
            'status' => 'planning',
        ]);
    }

    /**
     * Project with specific customer
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
            'name' => 'Project for ' . $customer->name,
        ]);
    }

    /**
     * Project with specific supplier
     */
    public function withSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
        ]);
    }

    /**
     * Project with specific solar plant
     */
    public function forSolarPlant(SolarPlant $solarPlant): static
    {
        return $this->state(fn (array $attributes) => [
            'solar_plant_id' => $solarPlant->id,
            'name' => 'Project for ' . $solarPlant->name,
        ]);
    }

    /**
     * Minimal project for testing
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test Project',
            'status' => 'planning',
            'budget' => 25000.00,
            'progress_percentage' => 0,
        ]);
    }
}