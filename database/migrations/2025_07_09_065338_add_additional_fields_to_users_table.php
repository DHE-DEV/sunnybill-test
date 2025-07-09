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
        Schema::table('users', function (Blueprint $table) {
            // Anrede (Herr, Frau)
            $table->enum('salutation', ['herr', 'frau'])->nullable()->after('name');
            
            // Namenskürzel
            $table->string('name_abbreviation', 10)->nullable()->after('salutation');
            
            // Ich/Du Spalte
            $table->enum('address_form', ['ich', 'du'])->default('du')->after('name_abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salutation', 'name_abbreviation', 'address_form']);
        });
    }
};
