<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 80)->unique();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['token']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_tokens');
    }
};
