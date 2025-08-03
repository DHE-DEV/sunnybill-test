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
        // MySQL benötigt eine komplette Neudefinition des ENUM-Feldes
        DB::statement("ALTER TABLE customers MODIFY COLUMN payment_method ENUM('transfer', 'direct_debit', 'sepa_direct_debit', 'sepa_bulk_transfer') NULL COMMENT 'Zahlungsart: transfer=Überweisung, direct_debit=Lastschrift (Einzeln), sepa_direct_debit=SEPA Sammellastschrift, sepa_bulk_transfer=SEPA Sammelüberweisung'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rückgängig machen: Enum wieder auf ursprüngliche drei Werte reduzieren
        // Zuerst prüfen ob jemand die neue Option verwendet
        $countBulkTransfer = DB::table('customers')->where('payment_method', 'sepa_bulk_transfer')->count();
        
        if ($countBulkTransfer > 0) {
            throw new \Exception("Cannot rollback migration: {$countBulkTransfer} customers are using 'sepa_bulk_transfer' payment method. Please update these records first.");
        }
        
        DB::statement("ALTER TABLE customers MODIFY COLUMN payment_method ENUM('transfer', 'direct_debit', 'sepa_direct_debit') NULL COMMENT 'Zahlungsart: transfer=Überweisung, direct_debit=Lastschrift (Einzeln), sepa_direct_debit=SEPA Sammellastschrift'");
    }
};
