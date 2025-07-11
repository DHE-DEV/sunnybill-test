<?php

namespace Database\Factories;

use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'company_legal_form' => $this->faker->randomElement(['GmbH', 'AG', 'KG', 'OHG']),
            'company_address' => $this->faker->streetAddress(),
            'company_postal_code' => $this->faker->postcode(),
            'company_city' => $this->faker->city(),
            'company_country' => 'Deutschland',
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'tax_number' => $this->faker->numerify('##/###/####'),
            'vat_id' => $this->faker->regexify('DE[0-9]{9}'),
            'commercial_register' => 'Amtsgericht ' . $this->faker->city(),
            'commercial_register_number' => $this->faker->regexify('HRB [0-9]{5}'),
            'management' => $this->faker->name(),
            'default_payment_days' => 14,
            'payment_terms' => 'Zahlung innerhalb von 14 Tagen ohne Abzug.',
            'bank_name' => $this->faker->randomElement([
                'Sparkasse', 'Deutsche Bank', 'Commerzbank', 'Volksbank', 'Postbank'
            ]),
            'iban' => $this->faker->iban('DE'),
            'bic' => $this->faker->regexify('[A-Z]{4}DE[A-Z0-9]{2}[A-Z0-9]{3}'),
            'customer_number_prefix' => 'K',
        ];
    }

    /**
     * Indicate that this is the current/active company setting.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}