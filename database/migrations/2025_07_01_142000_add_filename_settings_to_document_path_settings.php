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
        Schema::table('document_path_settings', function (Blueprint $table) {
            // Dateinamen-Konfiguration
            $table->enum('filename_strategy', ['original', 'random', 'template'])
                ->default('original')
                ->after('path_template')
                ->comment('Strategie für Dateinamen: original, random oder template');
            
            $table->string('filename_template')->nullable()
                ->after('filename_strategy')
                ->comment('Template für Dateinamen (nur bei template-Strategie)');
            
            $table->string('filename_prefix')->nullable()
                ->after('filename_template')
                ->comment('Präfix für Dateinamen');
            
            $table->string('filename_suffix')->nullable()
                ->after('filename_prefix')
                ->comment('Suffix für Dateinamen (vor Dateierweiterung)');
            
            $table->boolean('preserve_extension')->default(true)
                ->after('filename_suffix')
                ->comment('Dateierweiterung beibehalten');
            
            $table->boolean('sanitize_filename')->default(true)
                ->after('preserve_extension')
                ->comment('Dateinamen bereinigen (Sonderzeichen entfernen)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_path_settings', function (Blueprint $table) {
            $table->dropColumn([
                'filename_strategy',
                'filename_template',
                'filename_prefix',
                'filename_suffix',
                'preserve_extension',
                'sanitize_filename'
            ]);
        });
    }
};