<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SupplierContract;
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
        $contract = $this->getOwnerRecord();
        
        return DocumentUploadConfig::forSupplierContracts()
            ->setModel($contract)
            ->setAdditionalData([
                'supplier_contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'supplier_name' => $contract->supplier?->company_name,
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
}