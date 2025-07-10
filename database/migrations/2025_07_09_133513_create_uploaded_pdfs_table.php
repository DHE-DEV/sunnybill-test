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
        Schema::create('uploaded_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->default('application/pdf');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('analysis_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('analysis_data')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['analysis_status']);
            $table->index(['uploaded_by']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_pdfs');
    }
};