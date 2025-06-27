<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migriere bestehende Adressdaten von suppliers zu addresses
        $suppliers = DB::table('suppliers')->get();
        
        foreach ($suppliers as $supplier) {
            // Nur migrieren wenn Adressdaten vorhanden sind
            if ($supplier->address || $supplier->postal_code || $supplier->city) {
                DB::table('addresses')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'addressable_id' => $supplier->id,
                    'addressable_type' => 'App\Models\Supplier',
                    'type' => 'standard',
                    'company_name' => $supplier->company_name,
                    'street_address' => $supplier->address ?? '',
                    'postal_code' => $supplier->postal_code ?? '',
                    'city' => $supplier->city ?? '',
                    'country' => $supplier->country ?? 'Deutschland',
                    'label' => 'Hauptsitz',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lösche alle Supplier-Adressen
        DB::table('addresses')
            ->where('addressable_type', 'App\Models\Supplier')
            ->delete();
    }
};