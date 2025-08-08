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
            // Prüfe und füge nur fehlende Spalten hinzu
            if (!Schema::hasColumn('app_tokens', 'created_by_ip')) {
                $table->string('created_by_ip')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'app_type')) {
                $table->string('app_type')->default('mobile_app')->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'app_version')) {
                $table->string('app_version')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'device_info')) {
                $table->text('device_info')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'notes')) {
                $table->text('notes')->nullable()->after('expires_at');
            }
            
            // Ressourcen-Beschränkungen
            if (!Schema::hasColumn('app_tokens', 'allowed_customers')) {
                $table->json('allowed_customers')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'allowed_suppliers')) {
                $table->json('allowed_suppliers')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'allowed_solar_plants')) {
                $table->json('allowed_solar_plants')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'allowed_projects')) {
                $table->json('allowed_projects')->nullable()->after('expires_at');
            }
            
            if (!Schema::hasColumn('app_tokens', 'restrict_customers')) {
                $table->boolean('restrict_customers')->default(false)->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'restrict_suppliers')) {
                $table->boolean('restrict_suppliers')->default(false)->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'restrict_solar_plants')) {
                $table->boolean('restrict_solar_plants')->default(false)->after('expires_at');
            }
            if (!Schema::hasColumn('app_tokens', 'restrict_projects')) {
                $table->boolean('restrict_projects')->default(false)->after('expires_at');
            }
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
