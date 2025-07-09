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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contract_recognition_1')->nullable()->after('contract_number');
            $table->string('contract_recognition_2')->nullable()->after('contract_recognition_1');
            $table->string('contract_recognition_3')->nullable()->after('contract_recognition_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['contract_recognition_1', 'contract_recognition_2', 'contract_recognition_3']);
        });
    }
};
