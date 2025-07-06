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

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->documents()->count();
        return $count > 0 ? (string) $count : null;
    }

    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        $contract = $this->getOwnerRecord();
        
        // Verwende die neue DocumentUploadConfig mit DocumentPathSetting-Integration
        return DocumentUploadConfig::forSupplierContracts($contract)
            ->setAdditionalData([
                'supplier_contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'contract_internal_number' => $contract->contract_internal_number,
                'supplier_name' => $contract->supplier?->company_name,
                'supplier_number' => $contract->supplier?->supplier_number,
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
}
