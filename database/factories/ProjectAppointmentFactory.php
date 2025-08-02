<?php

namespace Database\Factories;

use App\Models\ProjectAppointment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectAppointment>
 */
class ProjectAppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+3 months');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');
        
        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->randomElement([
                'Site Inspection',
                'Client Meeting',
                'Installation Planning',
                'Progress Review',
                'Technical Review',
                'Final Walkthrough',
                'Training Session',
                'Maintenance Check'
            ]),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->faker->randomElement([
                'Customer Site',
                'Office',
                'Video Conference',
                'Installation Location',
                'Remote'
            ]) . ' - ' . $this->faker->address(),
            'appointment_type' => $this->faker->randomElement([
                'meeting',
                'inspection',
                'installation',
                'maintenance',
                'training',
                'consultation'
            ]),
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduled']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'organizer_id' => User::factory(),
            'attendees' => $this->faker->randomElements([
                'John Doe (Project Manager)',
                'Jane Smith (Technical Lead)',
                'Mike Johnson (Installation Team)',
                'Customer Representative',
                'Site Supervisor',
                'Quality Assurance',
            ], $this->faker->numberBetween(2, 4)),
            'external_attendees' => $this->faker->optional(0.6)->randomElements([
                'customer@example.com',
                'contractor@supplier.com',
                'inspector@authority.com',
            ], $this->faker->numberBetween(1, 2)),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'agenda' => $this->faker->optional(0.7)->randomElements([
                'Review project progress',
                'Discuss technical specifications',
                'Address concerns and questions',
                'Plan next steps',
                'Quality inspection',
                'Training on system operation',
            ], $this->faker->numberBetween(2, 4)),
            'reminder_sent' => $this->faker->boolean(70),
            'reminder_hours_before' => $this->faker->randomElement([24, 48, 72]),
            'is_recurring' => $this->faker->boolean(10),
            'recurring_pattern' => $this->faker->optional(0.1)->randomElement(['weekly', 'monthly']),
        ];
    }

    /**
     * Scheduled appointment
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'start_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Confirmed appointment
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'start_date' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
            'confirmed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * In progress appointment
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'start_date' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+2 hours'),
        ]);
    }

    /**
     * Completed appointment
     */
    public function completed(): static
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '-1 day');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'completed_at' => $endDate,
            'outcome_notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Cancelled appointment
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Rescheduled appointment
     */
    public function rescheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rescheduled',
            'original_start_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'rescheduled_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'rescheduled_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Site inspection appointment
     */
    public function siteInspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Site Inspection',
            'appointment_type' => 'inspection',
            'location' => 'Customer Site - ' . $this->faker->address(),
            'agenda' => [
                'Site assessment',
                'Measure installation area',
                'Check electrical infrastructure',
                'Identify potential obstacles',
                'Discuss timeline',
            ],
        ]);
    }

    /**
     * Client meeting appointment
     */
    public function clientMeeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Client Meeting',
            'appointment_type' => 'meeting',
            'location' => $this->faker->randomElement(['Office', 'Customer Site', 'Video Conference']),
            'agenda' => [
                'Project overview',
                'Timeline discussion',
                'Budget review',
                'Contract details',
                'Q&A session',
            ],
        ]);
    }

    /**
     * Installation appointment
     */
    public function installation(): static
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+2 months');
        $endDate = (clone $startDate)->modify('+8 hours'); // Full day installation
        
        return $this->state(fn (array $attributes) => [
            'title' => 'Solar Panel Installation',
            'appointment_type' => 'installation',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => 'Installation Site - ' . $this->faker->address(),
            'priority' => 'high',
            'attendees' => [
                'Installation Team Lead',
                'Electrical Technician',
                'Safety Supervisor',
                'Customer Representative',
            ],
        ]);
    }

    /**
     * Maintenance appointment
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Maintenance Check',
            'appointment_type' => 'maintenance',
            'location' => 'Solar Plant Location',
            'is_recurring' => true,
            'recurring_pattern' => 'monthly',
            'agenda' => [
                'Visual inspection',
                'Performance check',
                'Cleaning assessment',
                'Equipment verification',
            ],
        ]);
    }

    /**
     * Training session appointment
     */
    public function training(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'System Operation Training',
            'appointment_type' => 'training',
            'location' => 'Customer Site',
            'agenda' => [
                'System overview',
                'Operation procedures',
                'Monitoring interface',
                'Maintenance guidelines',
                'Emergency procedures',
            ],
        ]);
    }

    /**
     * Urgent appointment
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'start_date' => $this->faker->dateTimeBetween('now', '+3 days'),
            'reminder_hours_before' => 24,
        ]);
    }

    /**
     * Recurring appointment
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_pattern' => $this->faker->randomElement(['weekly', 'monthly']),
            'appointment_type' => $this->faker->randomElement(['maintenance', 'inspection']),
        ]);
    }

    /**
     * Appointment for specific project
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Appointment organized by specific user
     */
    public function organizedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'organizer_id' => $user->id,
        ]);
    }

    /**
     * Upcoming appointment (next 7 days)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'status' => $this->faker->randomElement(['scheduled', 'confirmed']),
        ]);
    }

    /**
     * Today's appointment
     */
    public function today(): static
    {
        $hour = $this->faker->numberBetween(8, 17);
        $startDate = now()->setTime($hour, 0);
        $endDate = (clone $startDate)->addHours($this->faker->numberBetween(1, 3));
        
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'in_progress']),
        ]);
    }

    /**
     * Minimal appointment for testing
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Test Appointment',
            'appointment_type' => 'meeting',
            'status' => 'scheduled',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(1)->addHours(2),
        ]);
    }
}