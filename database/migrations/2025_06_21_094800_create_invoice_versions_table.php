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
        Schema::create('invoice_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->integer('version_number');
            $table->json('invoice_data'); // Vollständige Rechnungsdaten als JSON
            $table->json('customer_data'); // Kundendaten zum Zeitpunkt der Version
            $table->json('items_data'); // Alle Rechnungsposten als JSON
            $table->string('changed_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->json('changed_fields')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index(['invoice_id', 'version_number']);
            $table->index(['invoice_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_versions');
    }
};