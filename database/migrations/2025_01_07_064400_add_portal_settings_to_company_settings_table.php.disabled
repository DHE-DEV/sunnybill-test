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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('portal_url')->nullable()->after('project_number_prefix');
            $table->string('portal_name')->nullable()->after('portal_url');
            $table->text('portal_description')->nullable()->after('portal_name');
            $table->boolean('portal_enabled')->default(true)->after('portal_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'portal_url',
                'portal_name', 
                'portal_description',
                'portal_enabled'
            ]);
        });
    }
};