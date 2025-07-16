<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

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
        $billing = $this->getOwnerRecord();
        
        return DocumentUploadConfig::forSupplierContractBillings()
            ->setModel($billing)
            ->setAdditionalData([
                'billing_id' => $billing->id,
                'billing_number' => $billing->billing_number,
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
}
