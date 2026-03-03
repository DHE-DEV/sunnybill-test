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
        Schema::table('customer_article', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->after('is_active');
            $table->date('valid_to')->nullable()->after('valid_from');
            $table->string('billing_type')->default('invoice')->after('valid_to');
        });
    }

    public function down(): void
    {
        Schema::table('customer_article', function (Blueprint $table) {
            $table->dropColumn(['valid_from', 'valid_to', 'billing_type']);
        });
    }
};
