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
            $table->unsignedBigInteger('company_setting_id')->nullable()->after('id');
            $table->foreign('company_setting_id')->references('id')->on('company_settings')->onDelete('set null');
        });
        
        // Set all existing users to company_setting_id = 1
        DB::table('users')->update(['company_setting_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_setting_id']);
            $table->dropColumn('company_setting_id');
        });
    }
};
