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
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->text('custom_field_1')->nullable()->after('notes');
            $table->text('custom_field_2')->nullable()->after('custom_field_1');
            $table->text('custom_field_3')->nullable()->after('custom_field_2');
            $table->text('custom_field_4')->nullable()->after('custom_field_3');
            $table->text('custom_field_5')->nullable()->after('custom_field_4');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn([
                'custom_field_1',
                'custom_field_2',
                'custom_field_3',
                'custom_field_4',
                'custom_field_5'
            ]);
        });
    }
};
