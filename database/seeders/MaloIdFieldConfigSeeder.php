<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FieldConfig;

class MaloIdFieldConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FieldConfig::updateOrCreate(
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'malo_id',
            ],
            [
                'field_label' => 'MaLo-ID',
                'field_type' => 'text',
                'field_description' => 'Marktlokations-ID',
                'section_name' => 'Vertragsdaten',
                'sort_order' => 5, // Adjust order to place it after external_contract_number
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true, // Mark as system field
                'field_options' => json_encode(['validation' => 'nullable|string|max:255']),
            ]
        );
    }
}
