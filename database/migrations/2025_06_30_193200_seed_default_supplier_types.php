<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $supplierTypes = [
            [
                'id' => Str::uuid(),
                'name' => 'Direktvermarkter',
                'description' => 'Direktvermarkter für Solarenergie',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Divers',
                'description' => 'Diverse Lieferanten',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Energieversorger',
                'description' => 'Energieversorger und Netzbetreiber',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('supplier_types')->insert($supplierTypes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('supplier_types')->whereIn('name', [
            'Direktvermarkter',
            'Divers', 
            'Energieversorger'
        ])->delete();
    }
};