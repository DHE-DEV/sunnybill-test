<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierContractFactory extends Factory
{
    protected $model = SupplierContract::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'contract_number' => $this->faker->unique()->numerify('CN-######'),
            'creditor_number' => $this->faker->optional()->numerify('CRED-######'),
            'external_contract_number' => $this->faker->optional()->bothify('EXT-??-####'),
            'title' => $this->faker->catchPhrase,
            'description' => $this->faker->paragraph,
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->optional(0.8)->dateTimeBetween('+1 month', '+2 years'),
            'contract_value' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated', 'completed']),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'On Receipt']),
            'notes' => $this->faker->optional()->paragraph,
            'is_active' => $this->faker->boolean(90),
            'created_by' => User::factory(),
        ];
    }
}
