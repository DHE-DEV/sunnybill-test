<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Supplier;
use App\Services\DocumentUploadConfig;
use App\Traits\DocumentUploadTrait;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumente';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    protected static ?string $icon = 'heroicon-o-document-text';

    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        $supplier = $this->getOwnerRecord();
        
        return DocumentUploadConfig::forSuppliers()
            ->setModel($supplier)
            ->setAdditionalData([
                'supplier_id' => $supplier->id,
                'supplier_number' => $supplier->supplier_number,
                'company_name' => $supplier->company_name,
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
}