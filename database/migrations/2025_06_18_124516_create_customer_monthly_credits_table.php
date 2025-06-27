<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('customer_monthly_credits')) {
            Schema::create('customer_monthly_credits', function (Blueprint $table) {
                $table->id();
                $table->uuid('customer_id');
                $table->uuid('solar_plant_id');
                $table->date('month'); // YYYY-MM-01 Format
                $table->decimal('participation_percentage', 5, 2);
                $table->decimal('energy_share_kwh', 15, 6);
                $table->decimal('savings_amount', 15, 6);
                $table->decimal('feed_in_revenue', 15, 6);
                $table->decimal('total_credit', 15, 6);
                $table->timestamps();
                
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
                
                // Unique constraint: Pro Kunde nur eine Gutschrift pro Monat pro Anlage
                $table->unique(['customer_id', 'solar_plant_id', 'month'], 'customer_monthly_credits_unique');
                
                $table->index('customer_id');
                $table->index('solar_plant_id');
                $table->index('month');
            });
        } else {
            // Tabelle existiert bereits, füge fehlende Spalten hinzu
            Schema::table('customer_monthly_credits', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_monthly_credits', 'solar_plant_id')) {
                    $table->uuid('solar_plant_id')->after('customer_id');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'month')) {
                    $table->date('month')->after('solar_plant_id');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'participation_percentage')) {
                    $table->decimal('participation_percentage', 5, 2)->after('month');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'energy_share_kwh')) {
                    $table->decimal('energy_share_kwh', 15, 6)->after('participation_percentage');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'savings_amount')) {
                    $table->decimal('savings_amount', 15, 6)->after('energy_share_kwh');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'feed_in_revenue')) {
                    $table->decimal('feed_in_revenue', 15, 6)->after('savings_amount');
                }
                if (!Schema::hasColumn('customer_monthly_credits', 'total_credit')) {
                    $table->decimal('total_credit', 15, 6)->after('feed_in_revenue');
                }
            });
            
            // Füge Foreign Keys hinzu falls sie nicht existieren
            try {
                Schema::table('customer_monthly_credits', function (Blueprint $table) {
                    if (Schema::hasColumn('customer_monthly_credits', 'solar_plant_id')) {
                        $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
                    }
                });
            } catch (\Exception $e) {
                // Foreign Key existiert bereits oder Tabelle existiert nicht
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_monthly_credits');
    }
};
