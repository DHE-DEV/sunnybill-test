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
        Schema::create('sim_cards', function (Blueprint $table) {
            $table->id();
            
            // Basic SIM card information
            $table->string('iccid')->unique()->comment('SIM card ICCID number');
            $table->string('msisdn')->nullable()->comment('Phone number');
            $table->string('imsi')->nullable()->comment('International Mobile Subscriber Identity');
            $table->string('pin_code')->nullable();
            $table->string('puk_code')->nullable();
            
            // Provider and contract information
            $table->string('provider')->comment('Mobile network provider');
            $table->string('tariff')->nullable()->comment('Current tariff plan');
            $table->enum('contract_type', ['prepaid', 'postpaid', 'iot'])->default('postpaid');
            $table->decimal('monthly_cost', 8, 2)->nullable()->comment('Monthly cost in EUR');
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            
            // Technical configuration
            $table->string('apn')->nullable()->comment('Access Point Name');
            $table->bigInteger('data_limit_mb')->nullable()->comment('Monthly data limit in MB');
            $table->bigInteger('data_used_mb')->default(0)->comment('Data used this month in MB');
            
            // Status and assignment
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended', 'expired'])->default('active');
            $table->timestamp('last_activity')->nullable();
            
            // Assignment to router (optional foreign key)
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('set null');
            $table->string('assigned_to')->nullable()->comment('Device or person assigned to');
            $table->string('location')->nullable();
            
            // Additional information
            $table->text('description')->nullable();
            $table->json('additional_data')->nullable()->comment('Additional metadata');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['provider', 'status']);
            $table->index(['is_active', 'is_blocked']);
            $table->index('contract_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sim_cards');
    }
};
