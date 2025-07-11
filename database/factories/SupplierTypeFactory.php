<?php

namespace Database\Factories;

use App\Models\SupplierType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierType>
 */
class SupplierTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupplierType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Direktvermarkter',
                'Installateur',
                'Wartungsunternehmen',
                'Komponentenhersteller',
                'Energieversorger',
                'Beratungsunternehmen',
                'Finanzdienstleister',
                'Versicherung',
                'Rechtsberatung',
                'Steuerberatung',
                'Sonstiges'
            ]),
            'description' => $this->faker->optional(0.7)->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(95), // 95% aktiv
        ];
    }

    /**
     * Indicate that the supplier type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a specific supplier type.
     */
    public function direktvermarkter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Direktvermarkter',
            'description' => 'Unternehmen für die Direktvermarktung von Solarstrom',
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a specific supplier type.
     */
    public function installateur(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Installateur',
            'description' => 'Installationsunternehmen für Solaranlagen',
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a specific supplier type.
     */
    public function wartung(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Wartungsunternehmen',
            'description' => 'Unternehmen für Wartung und Service von Solaranlagen',
            'sort_order' => 3,
        ]);
    }
}