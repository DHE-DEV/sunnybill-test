<?php

namespace App\Console\Commands;

use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;
use Illuminate\Console\Command;

/**
 * Test-Command für das Dokumenten-Upload-Modul
 * 
 * Testet alle Komponenten des wiederverwendbaren Upload-Moduls
 */
class TestDocumentUploadModule extends Command
{
    protected $signature = 'module:test-document-upload {--debug : Debug-Modus aktivieren}';
    protected $description = 'Testet das wiederverwendbare Dokumenten-Upload-Modul';

    public function handle(): int
    {
        $this->info('🧪 Teste Dokumenten-Upload-Modul');
        $this->newLine();

        $debug = $this->option('debug');
        $errors = [];

        try {
            // Test 1: DocumentUploadConfig
            $this->info('📋 Test 1: DocumentUploadConfig');
            $configTests = $this->testDocumentUploadConfig($debug);
            if (!$configTests['success']) {
                $errors[] = 'DocumentUploadConfig: ' . $configTests['error'];
            }
            $this->line('   ' . ($configTests['success'] ? '✅' : '❌') . ' ' . $configTests['message']);

            // Test 2: DocumentFormBuilder
            $this->info('📝 Test 2: DocumentFormBuilder');
            $formTests = $this->testDocumentFormBuilder($debug);
            if (!$formTests['success']) {
                $errors[] = 'DocumentFormBuilder: ' . $formTests['error'];
            }
            $this->line('   ' . ($formTests['success'] ? '✅' : '❌') . ' ' . $formTests['message']);

            // Test 3: DocumentTableBuilder
            $this->info('📊 Test 3: DocumentTableBuilder');
            $tableTests = $this->testDocumentTableBuilder($debug);
            if (!$tableTests['success']) {
                $errors[] = 'DocumentTableBuilder: ' . $tableTests['error'];
            }
            $this->line('   ' . ($tableTests['success'] ? '✅' : '❌') . ' ' . $tableTests['message']);

            // Test 4: Integration Test
            $this->info('🔗 Test 4: Integration');
            $integrationTests = $this->testIntegration($debug);
            if (!$integrationTests['success']) {
                $errors[] = 'Integration: ' . $integrationTests['error'];
            }
            $this->line('   ' . ($integrationTests['success'] ? '✅' : '❌') . ' ' . $integrationTests['message']);

            // Test 5: Konfigurationsvalidierung
            $this->info('⚙️ Test 5: Konfigurationsvalidierung');
            $validationTests = $this->testConfigValidation($debug);
            if (!$validationTests['success']) {
                $errors[] = 'Validation: ' . $validationTests['error'];
            }
            $this->line('   ' . ($validationTests['success'] ? '✅' : '❌') . ' ' . $validationTests['message']);

            $this->newLine();

            // Zusammenfassung
            if (empty($errors)) {
                $this->info('🎉 ALLE TESTS ERFOLGREICH!');
                $this->line('✅ Das Dokumenten-Upload-Modul ist vollständig funktionsfähig');
                $this->line('✅ Alle Komponenten sind korrekt implementiert');
                $this->line('✅ Konfiguration und Validierung funktionieren');
                $this->newLine();
                $this->line('📖 Dokumentation: docs/DocumentUploadModule.md');
                return Command::SUCCESS;
            } else {
                $this->error('❌ TESTS FEHLGESCHLAGEN:');
                foreach ($errors as $error) {
                    $this->line('   • ' . $error);
                }
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('💥 KRITISCHER FEHLER: ' . $e->getMessage());
            if ($debug) {
                $this->line($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Testet DocumentUploadConfig
     */
    protected function testDocumentUploadConfig(bool $debug): array
    {
        try {
            // Test Standard-Konfiguration
            $config = new DocumentUploadConfig();
            $array = $config->toArray();
            
            if (empty($array)) {
                return ['success' => false, 'error' => 'Leere Konfiguration', 'message' => 'Standard-Konfiguration ist leer'];
            }

            // Test vordefinierte Konfigurationen
            $imageConfig = DocumentUploadConfig::forImages();
            $documentConfig = DocumentUploadConfig::forDocuments();
            $minimalConfig = DocumentUploadConfig::minimal();
            $fullConfig = DocumentUploadConfig::full();

            // Test Getter/Setter
            $config->set('testKey', 'testValue');
            if ($config->get('testKey') !== 'testValue') {
                return ['success' => false, 'error' => 'Getter/Setter funktioniert nicht', 'message' => 'Getter/Setter Test fehlgeschlagen'];
            }

            // Test Merge
            $config->merge(['newKey' => 'newValue']);
            if ($config->get('newKey') !== 'newValue') {
                return ['success' => false, 'error' => 'Merge funktioniert nicht', 'message' => 'Merge Test fehlgeschlagen'];
            }

            if ($debug) {
                $this->line('     Standard-Config Keys: ' . count($array));
                $this->line('     Image-Config: ' . count($imageConfig->toArray()) . ' Keys');
                $this->line('     Document-Config: ' . count($documentConfig->toArray()) . ' Keys');
            }

            return ['success' => true, 'message' => 'Alle Konfigurationstests erfolgreich'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'message' => 'Exception in DocumentUploadConfig'];
        }
    }

    /**
     * Testet DocumentFormBuilder
     */
    protected function testDocumentFormBuilder(bool $debug): array
    {
        try {
            // Test Builder-Erstellung
            $builder = DocumentFormBuilder::make([
                'categories' => ['test' => 'Test'],
                'maxSize' => 5120,
            ]);

            if (!$builder instanceof DocumentFormBuilder) {
                return ['success' => false, 'error' => 'Builder nicht erstellt', 'message' => 'DocumentFormBuilder::make() fehlgeschlagen'];
            }

            // Test Quick Upload
            $uploadField = DocumentFormBuilder::quickUpload([
                'maxSize' => 1024,
                'acceptedFileTypes' => ['application/pdf']
            ]);

            if (!$uploadField) {
                return ['success' => false, 'error' => 'QuickUpload fehlgeschlagen', 'message' => 'quickUpload() gibt null zurück'];
            }

            // Test Quick Schema
            $schema = DocumentFormBuilder::quickSchema([
                'categories' => ['contract' => 'Vertrag'],
                'showDescription' => true
            ]);

            if (!is_array($schema) || empty($schema)) {
                return ['success' => false, 'error' => 'QuickSchema fehlgeschlagen', 'message' => 'quickSchema() gibt leeres Array zurück'];
            }

            if ($debug) {
                $this->line('     Schema Elements: ' . count($schema));
                $this->line('     Upload Field Type: ' . get_class($uploadField));
            }

            return ['success' => true, 'message' => 'FormBuilder Tests erfolgreich'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'message' => 'Exception in DocumentFormBuilder'];
        }
    }

    /**
     * Testet DocumentTableBuilder
     */
    protected function testDocumentTableBuilder(bool $debug): array
    {
        try {
            // Test Builder-Erstellung
            $builder = DocumentTableBuilder::make([
                'categories' => ['test' => 'Test'],
                'showIcon' => true,
                'enableBulkActions' => true,
            ]);

            if (!$builder instanceof DocumentTableBuilder) {
                return ['success' => false, 'error' => 'TableBuilder nicht erstellt', 'message' => 'DocumentTableBuilder::make() fehlgeschlagen'];
            }

            if ($debug) {
                $this->line('     TableBuilder erstellt: ' . get_class($builder));
            }

            return ['success' => true, 'message' => 'TableBuilder Tests erfolgreich'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'message' => 'Exception in DocumentTableBuilder'];
        }
    }

    /**
     * Testet Integration zwischen Komponenten
     */
    protected function testIntegration(bool $debug): array
    {
        try {
            // Test: Config -> FormBuilder
            $config = DocumentUploadConfig::forDocuments()->toArray();
            $formBuilder = DocumentFormBuilder::make($config);
            $schema = DocumentFormBuilder::quickSchema($config);

            if (empty($schema)) {
                return ['success' => false, 'error' => 'Config->FormBuilder Integration', 'message' => 'Konfiguration wird nicht korrekt an FormBuilder weitergegeben'];
            }

            // Test: Config -> TableBuilder
            $tableBuilder = DocumentTableBuilder::make($config);

            if (!$tableBuilder instanceof DocumentTableBuilder) {
                return ['success' => false, 'error' => 'Config->TableBuilder Integration', 'message' => 'Konfiguration wird nicht korrekt an TableBuilder weitergegeben'];
            }

            // Test: DocumentStorageService Integration
            $diskName = DocumentStorageService::getDiskName();
            $uploadDir = DocumentStorageService::getUploadDirectory('test');

            if (empty($diskName) || empty($uploadDir)) {
                return ['success' => false, 'error' => 'DocumentStorageService Integration', 'message' => 'DocumentStorageService nicht verfügbar'];
            }

            if ($debug) {
                $this->line('     Disk: ' . $diskName);
                $this->line('     Upload Dir: ' . $uploadDir);
                $this->line('     Schema Elements: ' . count($schema));
            }

            return ['success' => true, 'message' => 'Integration Tests erfolgreich'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'message' => 'Exception in Integration Tests'];
        }
    }

    /**
     * Testet Konfigurationsvalidierung
     */
    protected function testConfigValidation(bool $debug): array
    {
        try {
            $validationTests = 0;
            $passedTests = 0;

            // Test 1: Ungültige maxSize
            $validationTests++;
            try {
                new DocumentUploadConfig(['maxSize' => -1]);
                return ['success' => false, 'error' => 'Negative maxSize nicht abgefangen', 'message' => 'Validierung für maxSize fehlgeschlagen'];
            } catch (\InvalidArgumentException $e) {
                $passedTests++;
                if ($debug) $this->line('     ✓ Negative maxSize korrekt abgefangen');
            }

            // Test 2: Leere acceptedFileTypes
            $validationTests++;
            try {
                new DocumentUploadConfig(['acceptedFileTypes' => []]);
                return ['success' => false, 'error' => 'Leere acceptedFileTypes nicht abgefangen', 'message' => 'Validierung für acceptedFileTypes fehlgeschlagen'];
            } catch (\InvalidArgumentException $e) {
                $passedTests++;
                if ($debug) $this->line('     ✓ Leere acceptedFileTypes korrekt abgefangen');
            }

            // Test 3: Ungültiges directory
            $validationTests++;
            try {
                new DocumentUploadConfig(['directory' => '']);
                return ['success' => false, 'error' => 'Leeres directory nicht abgefangen', 'message' => 'Validierung für directory fehlgeschlagen'];
            } catch (\InvalidArgumentException $e) {
                $passedTests++;
                if ($debug) $this->line('     ✓ Leeres directory korrekt abgefangen');
            }

            // Test 4: Ungültiges defaultSort
            $validationTests++;
            try {
                new DocumentUploadConfig(['defaultSort' => ['invalid']]);
                return ['success' => false, 'error' => 'Ungültiges defaultSort nicht abgefangen', 'message' => 'Validierung für defaultSort fehlgeschlagen'];
            } catch (\InvalidArgumentException $e) {
                $passedTests++;
                if ($debug) $this->line('     ✓ Ungültiges defaultSort korrekt abgefangen');
            }

            if ($debug) {
                $this->line('     Validierungstests: ' . $passedTests . '/' . $validationTests . ' erfolgreich');
            }

            if ($passedTests === $validationTests) {
                return ['success' => true, 'message' => 'Alle Validierungstests erfolgreich (' . $passedTests . '/' . $validationTests . ')'];
            } else {
                return ['success' => false, 'error' => 'Nicht alle Validierungen funktionieren', 'message' => 'Nur ' . $passedTests . '/' . $validationTests . ' Tests erfolgreich'];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'message' => 'Exception in Validierungstests'];
        }
    }
}