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
        Schema::create('article_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('article_id');
            $table->integer('version_number')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['PRODUCT', 'SERVICE'])->default('SERVICE');
            $table->decimal('price', 10, 6);
            $table->decimal('tax_rate', 5, 4)->default(0.19);
            $table->string('unit')->default('Stück');
            $table->string('changed_by')->nullable(); // Wer hat die Änderung gemacht
            $table->text('change_reason')->nullable(); // Grund für die Änderung
            $table->json('changed_fields')->nullable(); // Welche Felder wurden geändert
            $table->boolean('is_current')->default(false); // Ist dies die aktuelle Version?
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->index(['article_id', 'version_number']);
            $table->index(['article_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_versions');
    }
};
