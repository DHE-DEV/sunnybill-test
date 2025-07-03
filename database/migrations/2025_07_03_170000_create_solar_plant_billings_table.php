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
        Schema::create('solar_plant_billings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('solar_plant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('customer_id')->constrained()->onDelete('cascade');
            $table->integer('billing_year');
            $table->integer('billing_month');
            $table->decimal('participation_percentage', 5, 2); // Beteiligungsprozentsatz
            $table->decimal('total_costs', 10, 2)->default(0); // Gesamtkosten für diesen Kunden
            $table->decimal('total_credits', 10, 2)->default(0); // Gesamtgutschriften für diesen Kunden
            $table->decimal('net_amount', 10, 2)->default(0); // Nettobetrag (Kosten - Gutschriften)
            $table->enum('status', ['draft', 'finalized', 'sent', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('cost_breakdown')->nullable(); // Aufschlüsselung der Kosten
            $table->json('credit_breakdown')->nullable(); // Aufschlüsselung der Gutschriften
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Eindeutige Kombination pro Kunde, Anlage und Monat
            $table->unique(['solar_plant_id', 'customer_id', 'billing_year', 'billing_month'], 'unique_solar_plant_customer_billing');
            
            // Indizes für bessere Performance
            $table->index(['billing_year', 'billing_month']);
            $table->index(['status']);
            $table->index(['solar_plant_id', 'billing_year', 'billing_month'], 'spb_plant_year_month_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_billings');
    }
};