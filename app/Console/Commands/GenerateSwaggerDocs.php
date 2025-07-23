<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate-from-openapi';
    protected $description = 'Generate Swagger documentation from existing OpenAPI file';

    public function handle()
    {
        $this->info('Generating Swagger documentation from OpenAPI file...');

        // Pfade
        $sourceFile = base_path('docs/openapi.yaml');
        $targetDir = storage_path('api-docs');
        $targetYamlFile = $targetDir . '/api-docs.yaml';
        $targetJsonFile = $targetDir . '/api-docs.json';

        // Überprüfe ob die Quelle existiert
        if (!File::exists($sourceFile)) {
            $this->error('OpenAPI source file not found: ' . $sourceFile);
            return 1;
        }

        // Erstelle Zielverzeichnis falls nötig
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            $this->info('Created directory: ' . $targetDir);
        }

        // Kopiere YAML-Datei
        File::copy($sourceFile, $targetYamlFile);
        $this->info('Copied YAML file to: ' . $targetYamlFile);

        // Konvertiere zu JSON
        try {
            $yamlContent = File::get($sourceFile);
            $phpArray = \Symfony\Component\Yaml\Yaml::parse($yamlContent);
            $jsonContent = json_encode($phpArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            File::put($targetJsonFile, $jsonContent);
            $this->info('Generated JSON file: ' . $targetJsonFile);
        } catch (\Exception $e) {
            $this->error('Error converting YAML to JSON: ' . $e->getMessage());
            return 1;
        }

        // Leere Laravel Cache für bessere Performance
        try {
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            $this->info('Cleared Laravel caches');
        } catch (\Exception $e) {
            $this->warn('Could not clear caches: ' . $e->getMessage());
        }

        $this->info('✅ Swagger documentation generated successfully!');
        $this->info('📖 Access documentation at: /api/documentation');
        
        return 0;
    }
}
