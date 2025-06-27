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
        Schema::create('tax_rate_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tax_rate_id');
            $table->integer('version_number');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('rate', 5, 4);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active');
            $table->boolean('is_default');
            $table->string('changed_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->json('changed_fields')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->index(['tax_rate_id', 'version_number']);
            $table->index(['tax_rate_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rate_versions');
    }
};