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
        Schema::create('router_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->string('webhook_token')->index();
            
            // Raw webhook data
            $table->json('raw_data'); // Complete raw webhook payload
            $table->json('headers')->nullable(); // HTTP headers
            $table->ipAddress('client_ip'); // IP address of sender
            $table->string('user_agent')->nullable();
            
            // Extracted key data for quick queries
            $table->string('operator')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->string('network_type', 50)->nullable();
            $table->integer('connection_time')->nullable(); // seconds
            $table->decimal('data_usage_mb', 10, 2)->nullable();
            $table->ipAddress('router_ip')->nullable();
            
            // Processing info
            $table->enum('status', ['success', 'validation_error', 'processing_error'])->default('success');
            $table->json('validation_errors')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('processing_time_ms', 8, 3)->nullable(); // Processing time in milliseconds
            
            // Response data
            $table->integer('http_response_code')->default(200);
            $table->json('response_data')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['router_id', 'created_at']);
            $table->index(['webhook_token', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['operator', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_webhook_logs');
    }
};
