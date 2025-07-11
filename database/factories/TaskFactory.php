<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled']),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'due_time' => $this->faker->optional(0.7)->time('H:i'),
            'labels' => $this->faker->optional(0.5)->randomElements(['wichtig', 'dringend', 'kunde', 'intern', 'wartung'], $this->faker->numberBetween(1, 3)),
            'order_index' => $this->faker->numberBetween(1, 100),
            'is_recurring' => $this->faker->boolean(20),
            'recurring_pattern' => $this->faker->optional(0.2)->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'estimated_minutes' => $this->faker->optional(0.8)->numberBetween(30, 480),
            'actual_minutes' => $this->faker->optional(0.3)->numberBetween(15, 600),
            'task_type_id' => TaskType::factory(),
            'customer_id' => null,
            'supplier_id' => null,
            'solar_plant_id' => null,
            'billing_id' => null,
            'milestone_id' => null,
            'assigned_to' => null,
            'owner_id' => null,
            'created_by' => null,
            'parent_task_id' => null,
            'completed_at' => null,
            'task_number' => null, // Wird automatisch generiert
        ];
    }

    /**
     * Task mit hoher Priorität
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Dringende Task
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Offene Task
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'completed_at' => null,
        ]);
    }

    /**
     * Task in Bearbeitung
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Abgeschlossene Task
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Überfällige Task
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'completed_at' => null,
        ]);
    }

    /**
     * Task für heute
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->toDateString(),
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'completed_at' => null,
        ]);
    }

    /**
     * Task für nächste 7 Tage
     */
    public function dueNext7Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'completed_at' => null,
        ]);
    }

    /**
     * Task für nächste 30 Tage
     */
    public function dueNext30Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'completed_at' => null,
        ]);
    }

    /**
     * Wiederkehrende Task
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_pattern' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
        ]);
    }

    /**
     * Task mit Kunde
     */
    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    /**
     * Task mit Lieferant
     */
    public function withSupplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => Supplier::factory(),
        ]);
    }

    /**
     * Task mit zugewiesenem Benutzer
     */
    public function withAssignedUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => User::factory(),
        ]);
    }

    /**
     * Task mit Besitzer
     */
    public function withOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => User::factory(),
        ]);
    }

    /**
     * Task mit Ersteller
     */
    public function withCreator(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => User::factory(),
        ]);
    }

    /**
     * Hauptaufgabe (keine Unteraufgabe)
     */
    public function mainTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_task_id' => null,
        ]);
    }

    /**
     * Unteraufgabe
     */
    public function subtask(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_task_id' => Task::factory(),
        ]);
    }

    /**
     * Minimale Task für Tests
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Test Task',
            'status' => 'open',
            'priority' => 'medium',
            'due_date' => now()->addDays(1),
        ]);
    }
}