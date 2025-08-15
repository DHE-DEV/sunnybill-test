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
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->string('default_title', 500)->nullable()->after('contract_recognition_3');
            $table->text('default_description')->nullable()->after('default_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn(['default_title', 'default_description']);
        });
    }
};
