<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solar_plant_monthly_billings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('solar_plant_id')->constrained('solar_plants')->cascadeOnDelete();
            $table->year('billing_year');
            $table->tinyInteger('billing_month');

            // Anlagendaten zum Zeitpunkt der Abrechnung
            $table->decimal('total_capacity_kwp', 14, 6)->default(0)->comment('Gesamtleistung der Anlage in kWp');
            $table->decimal('billed_kwp', 14, 6)->default(0)->comment('Abgerechnete kWp (Summe der Kundenbeteiligungen)');
            $table->decimal('difference_kwp', 14, 6)->default(0)->comment('Differenz kWp (Gesamt - Abgerechnet)');
            $table->decimal('difference_percentage', 8, 4)->default(0)->comment('Differenz in Prozent');

            // Energiedaten
            $table->decimal('produced_energy_kwh', 14, 3)->nullable()->comment('Erzeugte Energie in kWh');

            // Aggregierte Finanzdaten
            $table->decimal('total_costs', 14, 2)->default(0)->comment('Gesamtkosten brutto');
            $table->decimal('total_costs_net', 14, 2)->default(0)->comment('Gesamtkosten netto');
            $table->decimal('total_credits', 14, 2)->default(0)->comment('Gesamterlöse brutto');
            $table->decimal('total_credits_net', 14, 2)->default(0)->comment('Gesamterlöse netto');
            $table->decimal('total_vat_amount', 14, 2)->default(0)->comment('MwSt. gesamt');
            $table->decimal('net_amount', 14, 2)->default(0)->comment('Nettobetrag (Kosten - Erlöse)');

            // Kundenzähler
            $table->unsignedInteger('customer_count')->default(0)->comment('Anzahl abgerechneter Kunden');
            $table->decimal('total_participation_percentage', 8, 4)->default(0)->comment('Summe aller Beteiligungsprozente');

            // Status
            $table->string('status')->default('draft')->comment('draft, finalized, sent, paid');
            $table->text('notes')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['solar_plant_id', 'billing_year', 'billing_month'], 'unique_plant_year_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_plant_monthly_billings');
    }
};
