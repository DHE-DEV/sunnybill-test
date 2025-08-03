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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('payment_method', ['transfer', 'direct_debit', 'sepa_direct_debit'])
                ->nullable()
                ->after('account_holder')
                ->comment('Zahlungsart: transfer=Überweisung, direct_debit=Lastschrift (Einzeln), sepa_direct_debit=SEPA Sammellastschrift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
