<?php

namespace Database\Factories;

use App\Models\TaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskType>
 */
class TaskTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taskTypes = [
            'Allgemein',
            'Wartung',
            'Installation',
            'Beratung',
            'Support',
            'Planung',
            'Dokumentation',
            'Schulung',
            'Reparatur',
            'Inspektion',
            'Montage',
            'Demontage',
            'Konfiguration',
            'Test',
            'Qualitätskontrolle',
            'Inbetriebnahme',
            'Abnahme',
            'Projektmanagement',
            'Koordination',
            'Überwachung',
            'Analyse',
            'Optimierung',
            'Fehlerdiagnose',
            'Kalibrierung',
            'Reinigung',
            'Sicherheitsprüfung',
            'Compliance',
            'Audit',
            'Training',
            'Bestandsaufnahme'
        ];

        return [
            'name' => $this->faker->randomElement($taskTypes) . ' ' . $this->faker->numberBetween(1, 999),
            'description' => $this->faker->optional(0.7)->sentence(),
            'color' => $this->faker->hexColor(),
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Aktiver Task-Typ
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inaktiver Task-Typ
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Allgemeiner Task-Typ
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Allgemein',
            'description' => 'Allgemeine Aufgaben',
            'color' => '#6B7280',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Wartungs-Task-Typ
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Wartung',
            'description' => 'Wartungsaufgaben',
            'color' => '#F59E0B',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    /**
     * Installations-Task-Typ
     */
    public function installation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Installation',
            'description' => 'Installationsaufgaben',
            'color' => '#10B981',
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}