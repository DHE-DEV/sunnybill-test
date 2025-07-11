<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customerType = $this->faker->randomElement(['business', 'private']);
        
        return [
            'customer_number' => null, // Explizit null setzen für automatische Generierung
            'customer_type' => $customerType,
            'name' => $customerType === 'business' ? $this->faker->company() : $this->faker->name(),
            'company_name' => $customerType === 'business' ? $this->faker->company() : null,
            'contact_person' => $customerType === 'business' ? $this->faker->name() : null,
            'department' => $customerType === 'business' ? $this->faker->randomElement(['Einkauf', 'Vertrieb', 'Geschäftsführung', 'IT']) : null,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $customerType === 'business' ? $this->faker->phoneNumber() : null,
            'website' => $customerType === 'business' ? $this->faker->url() : null,
            'street' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->streetSuffix(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'state' => $this->faker->optional(0.7)->randomElement([
                'Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg', 'Bremen',
                'Hamburg', 'Hessen', 'Mecklenburg-Vorpommern', 'Niedersachsen',
                'Nordrhein-Westfalen', 'Rheinland-Pfalz', 'Saarland', 'Sachsen',
                'Sachsen-Anhalt', 'Schleswig-Holstein', 'Thüringen'
            ]),
            'country' => 'Deutschland',
            'country_code' => 'DE',
            'tax_number' => $customerType === 'business' ? $this->faker->optional(0.6)->numerify('##/###/####') : null,
            'vat_id' => $customerType === 'business' ? $this->faker->optional(0.8)->regexify('DE[0-9]{9}') : null,
            'payment_terms' => $this->faker->optional(0.7)->randomElement([
                'Zahlung innerhalb von 14 Tagen netto',
                'Zahlung innerhalb von 30 Tagen netto',
                'Zahlung sofort bei Erhalt'
            ]),
            'payment_days' => $this->faker->randomElement([14, 30, 7]),
            'bank_name' => $this->faker->optional(0.5)->randomElement([
                'Sparkasse', 'Deutsche Bank', 'Commerzbank', 'Volksbank', 'Postbank'
            ]),
            'iban' => $this->faker->optional(0.5)->iban('DE'),
            'bic' => $this->faker->optional(0.5)->regexify('[A-Z]{4}DE[A-Z0-9]{2}[A-Z0-9]{3}'),
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'is_active' => $this->faker->boolean(90), // 90% aktiv
            'deactivated_at' => null,
        ];
    }

    /**
     * Indicate that the customer is a business customer.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
            'company_name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'department' => $this->faker->randomElement(['Einkauf', 'Vertrieb', 'Geschäftsführung', 'IT']),
            'fax' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'tax_number' => $this->faker->numerify('##/###/####'),
            'vat_id' => $this->faker->regexify('DE[0-9]{9}'),
        ]);
    }

    /**
     * Indicate that the customer is a private customer.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'private',
            'name' => $this->faker->name(),
            'company_name' => null,
            'contact_person' => null,
            'department' => null,
            'fax' => null,
            'website' => null,
            'tax_number' => null,
            'vat_id' => null,
        ]);
    }

    /**
     * Indicate that the customer is deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the customer has Lexoffice synchronization.
     */
    public function withLexoffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'lexoffice_id' => $this->faker->uuid(),
            'lexoffice_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'lexware_version' => $this->faker->numberBetween(1, 10),
            'lexware_json' => [
                'id' => $this->faker->uuid(),
                'version' => $this->faker->numberBetween(1, 10),
                'organizationId' => $this->faker->uuid(),
            ],
        ]);
    }
}