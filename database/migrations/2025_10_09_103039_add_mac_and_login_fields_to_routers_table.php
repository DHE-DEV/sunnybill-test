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
        Schema::table('routers', function (Blueprint $table) {
            $table->string('lan_mac_address')->nullable()->after('serial_number');
            $table->string('login_username')->nullable()->after('description');
            $table->text('login_password')->nullable()->after('login_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['lan_mac_address', 'login_username', 'login_password']);
        });
    }
};
