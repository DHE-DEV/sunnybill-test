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
        Schema::table('app_tokens', function (Blueprint $table) {
            // Füge fehlende Spalten hinzu (ohne user_id zu ändern)
            $table->boolean('is_active')->default(true)->after('expires_at');
            $table->string('created_by_ip')->nullable()->after('is_active');
            $table->string('app_type')->default('mobile_app')->after('created_by_ip');
            $table->string('app_version')->nullable()->after('app_type');
            $table->text('device_info')->nullable()->after('app_version');
            $table->text('notes')->nullable()->after('device_info');
            
            // Ressourcen-Beschränkungen
            $table->json('allowed_customers')->nullable()->after('notes');
            $table->json('allowed_suppliers')->nullable()->after('allowed_customers');
            $table->json('allowed_solar_plants')->nullable()->after('allowed_suppliers');
            $table->json('allowed_projects')->nullable()->after('allowed_solar_plants');
            
            $table->boolean('restrict_customers')->default(false)->after('allowed_projects');
            $table->boolean('restrict_suppliers')->default(false)->after('restrict_customers');
            $table->boolean('restrict_solar_plants')->default(false)->after('restrict_suppliers');
            $table->boolean('restrict_projects')->default(false)->after('restrict_solar_plants');
            
            // Indices hinzufügen
            $table->index(['is_active']);
            $table->index(['app_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_tokens', function (Blueprint $table) {
            // Entferne die hinzugefügten Spalten
            $table->dropColumn([
                'is_active',
                'created_by_ip',
                'app_type',
                'app_version',
                'device_info',
                'notes',
                'allowed_customers',
                'allowed_suppliers',
                'allowed_solar_plants', 
                'allowed_projects',
                'restrict_customers',
                'restrict_suppliers',
                'restrict_solar_plants',
                'restrict_projects'
            ]);
        });
    }
};
