<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's check current enum values
        $enumValues = DB::select("SHOW COLUMNS FROM customers WHERE Field = 'customer_type'")[0]->Type;
        
        // Add 'lead' to the existing enum values if it doesn't exist
        if (strpos($enumValues, 'lead') === false) {
            DB::statement("ALTER TABLE customers MODIFY COLUMN customer_type ENUM('business', 'private', 'lead') DEFAULT 'business'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'lead' from enum and revert to original values
        DB::statement("ALTER TABLE customers MODIFY COLUMN customer_type ENUM('business', 'private') DEFAULT 'business'");
    }
};
