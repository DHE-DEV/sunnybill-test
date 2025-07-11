<?php

namespace Database\Factories;

use App\Models\SolarPlantMilestone;
use App\Models\SolarPlant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolarPlantMilestone>
 */
class SolarPlantMilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $milestoneTypes = [
            'Projektstart',
            'Planung abgeschlossen',
            'Genehmigung erhalten',
            'Material bestellt',
            'Installation begonnen',
            'Installation abgeschlossen',
            'Netzanschluss',
            'Inbetriebnahme',
            'Abnahme',
            'Projektabschluss'
        ];

        $statuses = ['planned', 'in_progress', 'completed', 'delayed', 'cancelled'];

        return [
            'title' => $this->faker->randomElement($milestoneTypes),
            'description' => $this->faker->optional(0.7)->paragraph(2),
            'planned_date' => $this->faker->dateTimeBetween('now', '+90 days'),
            'actual_date' => null,
            'status' => $this->faker->randomElement($statuses),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(90),
            'solar_plant_id' => SolarPlant::factory(),
            'project_manager_id' => null,
            'last_responsible_user_id' => null,
        ];
    }

    /**
     * Geplanter Meilenstein
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'actual_date' => null,
        ]);
    }

    /**
     * Meilenstein in Bearbeitung
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'actual_date' => null,
        ]);
    }

    /**
     * Abgeschlossener Meilenstein
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'actual_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Verspäteter Meilenstein
     */
    public function delayed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delayed',
            'planned_date' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
            'actual_date' => null,
        ]);
    }

    /**
     * Überfälliger Meilenstein
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'planned_date' => $this->faker->dateTimeBetween('-14 days', '-1 day'),
            'actual_date' => null,
        ]);
    }

    /**
     * Meilenstein für heute
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'planned_date' => now()->toDateString(),
            'status' => $this->faker->randomElement(['planned', 'in_progress']),
            'actual_date' => null,
        ]);
    }

    /**
     * Meilenstein für nächste 7 Tage
     */
    public function dueNext7Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'planned_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'status' => $this->faker->randomElement(['planned', 'in_progress']),
            'actual_date' => null,
        ]);
    }

    /**
     * Meilenstein für nächste 30 Tage
     */
    public function dueNext30Days(): static
    {
        return $this->state(fn (array $attributes) => [
            'planned_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['planned', 'in_progress']),
            'actual_date' => null,
        ]);
    }

    /**
     * Kritischer Meilenstein
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_critical' => true,
        ]);
    }

    /**
     * Meilenstein mit Projektmanager
     */
    public function withProjectManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_manager_id' => User::factory(),
        ]);
    }

    /**
     * Meilenstein mit letztem verantwortlichem Benutzer
     */
    public function withLastResponsibleUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_responsible_user_id' => User::factory(),
        ]);
    }

    /**
     * Meilenstein mit SolarPlant
     */
    public function withSolarPlant(): static
    {
        return $this->state(fn (array $attributes) => [
            'solar_plant_id' => SolarPlant::factory(),
        ]);
    }

    /**
     * Projektstart-Meilenstein
     */
    public function projectStart(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Projektstart',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Planungs-Meilenstein
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Planung abgeschlossen',
            'sort_order' => 2,
        ]);
    }

    /**
     * Installations-Meilenstein
     */
    public function installation(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Installation abgeschlossen',
            'sort_order' => 6,
            'is_active' => true,
        ]);
    }

    /**
     * Inbetriebnahme-Meilenstein
     */
    public function commissioning(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Inbetriebnahme',
            'sort_order' => 8,
            'is_active' => true,
        ]);
    }

    /**
     * Projektabschluss-Meilenstein
     */
    public function projectCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Projektabschluss',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * Minimaler Meilenstein für Tests
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Test Meilenstein',
            'status' => 'planned',
            'planned_date' => now()->addDays(1),
            'sort_order' => 1,
        ]);
    }
}