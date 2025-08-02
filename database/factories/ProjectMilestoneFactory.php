<?php

namespace Database\Factories;

use App\Models\ProjectMilestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectMilestone>
 */
class ProjectMilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = $this->faker->dateTimeBetween('now', '+6 months');
        
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->randomElement([
                'Project Kickoff',
                'Design Phase Complete',
                'Permits Obtained',
                'Installation Begin',
                'Testing Complete',
                'Final Inspection',
                'Project Handover',
                'Documentation Complete'
            ]),
            'description' => $this->faker->paragraph(),
            'due_date' => $dueDate,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'overdue', 'cancelled']),
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'assigned_to' => User::factory(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'order_index' => $this->faker->numberBetween(1, 10),
            'estimated_hours' => $this->faker->numberBetween(8, 120),
            'actual_hours' => $this->faker->optional(0.5)->numberBetween(5, 150),
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'completed_at' => null,
        ];
    }

    /**
     * Pending milestone
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'progress_percentage' => 0,
            'completed_at' => null,
            'actual_hours' => null,
        ]);
    }

    /**
     * In progress milestone
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'progress_percentage' => $this->faker->numberBetween(25, 75),
            'completed_at' => null,
            'actual_hours' => $this->faker->numberBetween(5, 80),
        ]);
    }

    /**
     * Completed milestone
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'actual_hours' => $this->faker->numberBetween(10, 120),
        ]);
    }

    /**
     * Overdue milestone
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
            'progress_percentage' => $this->faker->numberBetween(20, 80),
            'completed_at' => null,
        ]);
    }

    /**
     * Cancelled milestone
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
     * High priority milestone
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Urgent milestone
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'due_date' => $this->faker->dateTimeBetween('now', '+2 weeks'),
        ]);
    }

    /**
     * Project kickoff milestone
     */
    public function kickoff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Project Kickoff',
            'description' => 'Initial project meeting and setup',
            'order_index' => 1,
            'estimated_hours' => 8,
            'priority' => 'high',
        ]);
    }

    /**
     * Design phase milestone
     */
    public function designPhase(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Design Phase Complete',
            'description' => 'Complete system design and engineering drawings',
            'order_index' => 2,
            'estimated_hours' => 40,
            'priority' => 'high',
        ]);
    }

    /**
     * Installation milestone
     */
    public function installation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Installation Complete',
            'description' => 'Complete physical installation of solar system',
            'order_index' => 5,
            'estimated_hours' => 80,
            'priority' => 'medium',
        ]);
    }

    /**
     * Final inspection milestone
     */
    public function finalInspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Final Inspection',
            'description' => 'Final inspection and commissioning',
            'order_index' => 8,
            'estimated_hours' => 16,
            'priority' => 'high',
        ]);
    }

    /**
     * Milestone for specific project
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Milestone assigned to specific user
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Due this week
     */
    public function dueThisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'status' => $this->faker->randomElement(['pending', 'in_progress']),
        ]);
    }

    /**
     * Due next month
     */
    public function dueNextMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'status' => 'pending',
        ]);
    }

    /**
     * Minimal milestone for testing
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test Milestone',
            'status' => 'pending',
            'progress_percentage' => 0,
            'due_date' => now()->addDays(30),
        ]);
    }
}