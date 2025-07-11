<?php

namespace Database\Factories;

use App\Models\SolarPlant;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolarPlant>
 */
class SolarPlantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['geplant', 'in_bau', 'in_betrieb', 'wartung', 'stillgelegt'];

        return [
            'name' => 'Solaranlage ' . $this->faker->city(),
            'location' => $this->faker->address(),
            'description' => $this->faker->optional(0.7)->paragraph(2),
            'status' => $this->faker->randomElement($statuses),
            'total_capacity_kw' => $this->faker->randomFloat(2, 5, 1000),
            'expected_annual_yield_kwh' => $this->faker->numberBetween(5000, 1200000),
            'installation_date' => $this->faker->optional(0.8)->dateTimeBetween('-2 years', '+6 months'),
            'planned_installation_date' => $this->faker->optional(0.8)->dateTimeBetween('now', '+6 months'),
            'commissioning_date' => $this->faker->optional(0.6)->dateTimeBetween('-2 years', '+6 months'),
            'planned_commissioning_date' => $this->faker->optional(0.6)->dateTimeBetween('now', '+8 months'),
            'latitude' => $this->faker->optional(0.7)->latitude(47, 55),
            'longitude' => $this->faker->optional(0.7)->longitude(5, 15),
            'panel_count' => $this->faker->numberBetween(10, 500),
            'inverter_count' => $this->faker->numberBetween(1, 10),
            'battery_capacity_kwh' => $this->faker->optional(0.3)->randomFloat(2, 5, 100),
            'total_investment' => $this->faker->optional(0.8)->randomFloat(2, 10000, 500000),
            'annual_operating_costs' => $this->faker->optional(0.8)->randomFloat(2, 500, 5000),
            'feed_in_tariff_per_kwh' => $this->faker->optional(0.8)->randomFloat(6, 0.05, 0.15),
            'electricity_price_per_kwh' => $this->faker->optional(0.8)->randomFloat(6, 0.25, 0.35),
            'degradation_rate' => $this->faker->optional(0.8)->randomFloat(2, 0.3, 0.8),
            'is_active' => $this->faker->boolean(90),
            'notes' => $this->faker->optional(0.4)->paragraph(1),
            'fusion_solar_id' => $this->faker->optional(0.3)->uuid(),
            'last_sync_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Geplante Anlage
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'geplant',
            'installation_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'commissioning_date' => null,
        ]);
    }

    /**
     * Anlage im Bau
     */
    public function inConstruction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_bau',
            'installation_date' => $this->faker->dateTimeBetween('-1 month', '+2 months'),
            'commissioning_date' => null,
        ]);
    }

    /**
     * Anlage in Betrieb
     */
    public function operational(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_betrieb',
            'installation_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'commissioning_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    /**
     * Kleine Anlage
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_capacity_kw' => $this->faker->randomFloat(2, 5, 30),
            'panel_count' => $this->faker->numberBetween(10, 30),
            'expected_annual_yield_kwh' => $this->faker->numberBetween(5000, 35000),
        ]);
    }

    /**
     * Große Anlage
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_capacity_kw' => $this->faker->randomFloat(2, 200, 1000),
            'panel_count' => $this->faker->numberBetween(300, 500),
            'expected_annual_yield_kwh' => $this->faker->numberBetween(200000, 1200000),
        ]);
    }

    /**
     * Vollständige Anlage mit allen Beziehungen
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_betrieb',
            'installation_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'commissioning_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }

    /**
     * Minimale Anlage für Tests
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test Solaranlage',
            'status' => 'geplant',
            'total_capacity_kw' => 10.0,
            'panel_count' => 20,
        ]);
    }
}