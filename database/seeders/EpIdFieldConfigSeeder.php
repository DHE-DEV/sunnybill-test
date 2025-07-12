<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FieldConfig;

class EpIdFieldConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FieldConfig::updateOrCreate(
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'ep_id',
            ],
            [
                'field_label' => 'EP-ID',
                'field_type' => 'text',
                'field_description' => 'Einspeisepunkt-ID',
                'section_name' => 'Vertragsdaten',
                'sort_order' => 6, // After MaLo-ID
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
                'field_options' => json_encode(['validation' => 'nullable|string|max:255']),
            ]
        );
    }
}
