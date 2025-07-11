<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = $this->faker->company();
        
        return [
            'name' => $this->faker->name(),
            'company_name' => $companyName,
            'supplier_type_id' => SupplierType::factory(),
            'contact_person' => $this->faker->name(),
            'department' => $this->faker->randomElement(['Vertrieb', 'Einkauf', 'Technik', 'Kundenservice', 'Geschäftsführung']),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->optional(0.3)->phoneNumber(),
            'website' => $this->faker->optional(0.7)->url(),
            'tax_number' => $this->faker->optional(0.8)->numerify('###/###/####'),
            'vat_id' => $this->faker->optional(0.9)->regexify('DE[0-9]{9}'),
            'commercial_register' => $this->faker->optional(0.6)->regexify('HRB [0-9]{4,6}'),
            'street' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.2)->secondaryAddress(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'state' => $this->faker->optional(0.3)->state(),
            'country' => $this->faker->randomElement(['Deutschland', 'Österreich', 'Schweiz']),
            'country_code' => $this->faker->randomElement(['DE', 'AT', 'CH']),
            'bank_name' => $this->faker->optional(0.8)->company() . ' Bank',
            'iban' => $this->faker->optional(0.8)->iban('DE'),
            'bic' => $this->faker->optional(0.8)->regexify('[A-Z]{4}DE[A-Z0-9]{2}[A-Z0-9]{3}'),
            'account_holder' => $this->faker->optional(0.8)->name(),
            'payment_terms' => $this->faker->optional(0.7)->randomElement(['Sofort', '14 Tage netto', '30 Tage netto', '60 Tage netto']),
            'payment_days' => $this->faker->randomElement([0, 14, 30, 60]),
            'discount_percentage' => $this->faker->optional(0.4)->randomFloat(2, 0, 10),
            'discount_days' => $this->faker->optional(0.4)->randomElement([7, 10, 14]),
            'creditor_number' => $this->faker->optional(0.8)->numerify('K####'),
            'contract_number' => $this->faker->optional(0.7)->regexify('V-[0-9]{4}-[0-9]{3}'),
            'contract_recognition_1' => $this->faker->optional(0.6)->word(),
            'contract_recognition_2' => $this->faker->optional(0.4)->word(),
            'contract_recognition_3' => $this->faker->optional(0.2)->word(),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'is_active' => $this->faker->boolean(90), // 90% aktiv
            'lexoffice_id' => null, // Standardmäßig nicht synchronisiert
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the supplier is synced with Lexoffice.
     */
    public function syncedWithLexoffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'lexoffice_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the supplier has minimal data (only required fields).
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'contact_person' => null,
            'department' => null,
            'email' => null,
            'phone' => null,
            'fax' => null,
            'website' => null,
            'tax_number' => null,
            'vat_id' => null,
            'commercial_register' => null,
            'address_line_2' => null,
            'state' => null,
            'country_code' => null,
            'bank_name' => null,
            'iban' => null,
            'bic' => null,
            'account_holder' => null,
            'payment_terms' => null,
            'discount_percentage' => null,
            'discount_days' => null,
            'creditor_number' => null,
            'contract_number' => null,
            'contract_recognition_1' => null,
            'contract_recognition_2' => null,
            'contract_recognition_3' => null,
            'notes' => null,
            // company_name bleibt erforderlich
        ]);
    }

    /**
     * Indicate that the supplier has only a name (no company).
     */
    public function personalSupplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => null,
            'name' => $this->faker->name(),
        ]);
    }

    /**
     * Indicate that the supplier is a German company with German VAT ID.
     */
    public function german(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'Deutschland',
            'country_code' => 'DE',
            'vat_id' => $this->faker->regexify('DE[0-9]{9}'),
            'tax_number' => $this->faker->numerify('###/###/####'),
            'postal_code' => $this->faker->regexify('[0-9]{5}'),
        ]);
    }

    /**
     * Indicate that the supplier has complete banking information.
     */
    public function withBankingInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => $this->faker->company() . ' Bank',
            'iban' => $this->faker->iban('DE'),
            'bic' => $this->faker->regexify('[A-Z]{4}DE[A-Z0-9]{2}[A-Z0-9]{3}'),
            'account_holder' => $attributes['company_name'],
        ]);
    }

    /**
     * Indicate that the supplier has payment terms with discount.
     */
    public function withPaymentDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_terms' => '30 Tage netto, 2% Skonto bei Zahlung innerhalb 10 Tagen',
            'payment_days' => 30,
            'discount_percentage' => 2.0,
            'discount_days' => 10,
        ]);
    }

    /**
     * Indicate that the supplier has contract recognition patterns.
     */
    public function withContractRecognition(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_recognition_1' => $this->faker->word(),
            'contract_recognition_2' => $this->faker->word(),
            'contract_recognition_3' => $this->faker->word(),
        ]);
    }
}