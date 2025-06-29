<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('storage_driver')->default('local'); // local, s3, digitalocean
            $table->json('storage_config')->nullable(); // Konfiguration für Cloud-Speicher
            $table->bigInteger('total_storage_used')->default(0); // in Bytes
            $table->timestamp('last_storage_calculation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Standard-Einstellung erstellen
        DB::table('storage_settings')->insert([
            'storage_driver' => 'local',
            'storage_config' => json_encode([]),
            'total_storage_used' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};