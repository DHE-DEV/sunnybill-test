<?php

namespace App\Filament\Components;

use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use App\Services\DocumentStorageService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Standalone Dokumenten-Upload-Komponente
 * 
 * Verwendung:
 * 
 * <livewire:document-upload-component 
 *     :model="$supplier" 
 *     relationship="documents"
 *     :config="[
 *         'directory' => 'supplier-docs',
 *         'categories' => ['contract' => 'Vertrag', 'invoice' => 'Rechnung'],
 *         'maxSize' => 20480
 *     ]"
 * />
 */
class DocumentUploadComponent extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Model $model;
    public string $relationship;
    public array $config = [];
    public bool $showForm = false;

    protected $listeners = [
        'refreshDocuments' => '$refresh',
        'documentUploaded' => 'handleDocumentUploaded',
    ];

    public function mount(Model $model, string $relationship, array $config = []): void
    {
        $this->model = $model;
        $this->relationship = $relationship;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Standard-Konfiguration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'directory' => 'documents',
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'manual' => 'Handbuch',
                'photo' => 'Foto',
                'plan' => 'Plan/Zeichnung',
                'report' => 'Bericht',
                'correspondence' => 'Korrespondenz',
                'other' => 'Sonstiges',
            ],
            'maxSize' => 10240,
            'acceptedFileTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'application/zip',
                'application/x-rar-compressed',
            ],
            'title' => 'Dokumente',
            'createButtonLabel' => 'Dokument hinzufügen',
            'emptyStateHeading' => 'Keine Dokumente vorhanden',
            'emptyStateDescription' => 'Fügen Sie das erste Dokument hinzu.',
            'showUploadForm' => true,
            'showTable' => true,
            'modalWidth' => '4xl',
        ];
    }

    /**
     * Erstellt das Upload-Formular
     */
    protected function getFormSchema(): array
    {
        return DocumentFormBuilder::make($this->config)->getFormSchema();
    }

    /**
     * Formular-Konfiguration
     */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data')
            ->model($this->getRelationship()->getRelated());
    }

    /**
     * Tabellen-Konfiguration
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return DocumentTableBuilder::make($this->config)
            ->build($table)
            ->query($this->getRelationship())
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label($this->config['createButtonLabel'])
                    ->icon('heroicon-o-plus')
                    ->action('openCreateModal')
                    ->visible($this->config['showUploadForm'] ?? true),
            ]);
    }

    /**
     * Öffnet das Upload-Modal
     */
    public function openCreateModal(): void
    {
        $this->showForm = true;
        $this->form->fill();
    }

    /**
     * Schließt das Upload-Modal
     */
    public function closeCreateModal(): void
    {
        $this->showForm = false;
        $this->form->fill();
    }

    /**
     * Erstellt ein neues Dokument
     */
    public function create(): void
    {
        $data = $this->form->getState();
        
        // Verarbeite Upload-Daten
        $data = $this->processUploadData($data);
        
        // Erstelle das Dokument
        $this->getRelationship()->create($data);
        
        // Schließe Modal und aktualisiere
        $this->closeCreateModal();
        $this->dispatch('documentUploaded');
        
        // Benachrichtigung
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dokument erfolgreich hochgeladen!'
        ]);
    }

    /**
     * Verarbeitet Upload-Daten und extrahiert Metadaten
     */
    protected function processUploadData(array $data): array
    {
        if (isset($data['path']) && $data['path']) {
            $filePath = is_array($data['path']) ? $data['path'][0] ?? null : $data['path'];
            
            if ($filePath) {
                try {
                    $metadata = DocumentStorageService::extractFileMetadata($filePath);
                    $data = array_merge($data, $metadata);
                    $data['path'] = $filePath;
                } catch (\Exception $e) {
                    \Log::error('Fehler beim Extrahieren der Metadaten in DocumentUploadComponent', [
                        'file_path' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $data;
    }

    /**
     * Event-Handler für erfolgreiches Upload
     */
    public function handleDocumentUploaded(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Gibt die Relationship zurück
     */
    protected function getRelationship(): Relation
    {
        return $this->model->{$this->relationship}();
    }

    /**
     * Quick Upload Methode für einfache Uploads
     */
    public function quickUpload(string $filePath, array $metadata = []): void
    {
        $data = array_merge([
            'path' => $filePath,
            'name' => $metadata['name'] ?? pathinfo($filePath, PATHINFO_FILENAME),
            'category' => $metadata['category'] ?? 'other',
            'description' => $metadata['description'] ?? null,
        ], $metadata);

        $data = $this->processUploadData($data);
        $this->getRelationship()->create($data);
        
        $this->dispatch('documentUploaded');
    }

    /**
     * Bulk Upload Methode
     */
    public function bulkUpload(array $files): void
    {
        foreach ($files as $file) {
            $this->quickUpload($file['path'], $file['metadata'] ?? []);
        }
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($files) . ' Dokumente erfolgreich hochgeladen!'
        ]);
    }

    /**
     * Render-Methode
     */
    public function render()
    {
        return view('filament.components.document-upload-component');
    }

    /**
     * Statische Factory-Methode für einfache Erstellung
     */
    public static function make(Model $model, string $relationship, array $config = []): string
    {
        return view('filament.components.document-upload-component', [
            'model' => $model,
            'relationship' => $relationship,
            'config' => $config,
        ])->render();
    }
}