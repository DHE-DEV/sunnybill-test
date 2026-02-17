<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alten Unique-Constraint entfernen
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropUnique('unique_solar_plant_customer_billing');
        });

        // Virtuelle Spalte: '1' für aktive Abrechnungen, NULL für stornierte/gelöschte
        // MySQL erlaubt mehrere NULL-Werte in Unique-Indexes → stornierte blockieren nicht
        DB::statement("
            ALTER TABLE solar_plant_billings
            ADD COLUMN billing_active_flag CHAR(1) GENERATED ALWAYS AS (
                CASE WHEN status != 'cancelled' AND deleted_at IS NULL THEN '1' ELSE NULL END
            ) VIRTUAL
        ");

        // Neuer Unique-Constraint mit der virtuellen Spalte
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->unique(
                ['solar_plant_id', 'customer_id', 'billing_year', 'billing_month', 'billing_active_flag'],
                'unique_active_solar_plant_customer_billing'
            );
        });
    }

    public function down(): void
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropUnique('unique_active_solar_plant_customer_billing');
        });

        DB::statement('ALTER TABLE solar_plant_billings DROP COLUMN billing_active_flag');

        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->unique(
                ['solar_plant_id', 'customer_id', 'billing_year', 'billing_month'],
                'unique_solar_plant_customer_billing'
            );
        });
    }
};
