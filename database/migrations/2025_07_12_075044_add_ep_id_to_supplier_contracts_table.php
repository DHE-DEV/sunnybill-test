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
            $table->string('ep_id')->nullable()->after('malo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn('ep_id');
        });
    }
};
