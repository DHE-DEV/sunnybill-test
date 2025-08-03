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
        Schema::create('solar_plant_billing_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('solar_plant_billing_id')->constrained('solar_plant_billings')->onDelete('cascade');
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->foreign('recorded_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->enum('payment_type', [
                'bank_transfer',        // Überweisung
                'instant_transfer',     // Sofortüberweisung  
                'direct_debit',         // Abbuchung/Lastschrift
                'cash',                 // Barzahlung
                'check',                // Scheck
                'credit_card',          // Kreditkarte
                'paypal',               // PayPal
                'other'                 // Sonstiges
            ]);
            
            $table->decimal('amount', 10, 2); // Zahlungsbetrag
            $table->text('notes')->nullable(); // Zusätzliche Notizen zur Zahlung
            $table->string('reference')->nullable(); // Referenz/Verwendungszweck
            $table->date('payment_date'); // Datum der Zahlung (kann vom Erfassungsdatum abweichen)
            
            $table->timestamps();
            
            // Indizes
            $table->index(['solar_plant_billing_id', 'payment_date'], 'spb_payments_billing_date_idx');
            $table->index('recorded_by_user_id', 'spb_payments_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_billing_payments');
    }
};
