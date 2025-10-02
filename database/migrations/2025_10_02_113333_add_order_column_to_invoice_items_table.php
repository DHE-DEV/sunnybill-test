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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('description');
        });

        // Setze die Reihenfolge für bestehende Items basierend auf ihrer ID
        $invoices = DB::table('invoices')->pluck('id');
        foreach ($invoices as $invoiceId) {
            $items = DB::table('invoice_items')
                ->where('invoice_id', $invoiceId)
                ->orderBy('id')
                ->get();

            foreach ($items as $index => $item) {
                DB::table('invoice_items')
                    ->where('id', $item->id)
                    ->update(['order' => $index + 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
