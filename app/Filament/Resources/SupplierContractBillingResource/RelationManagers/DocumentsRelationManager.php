<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

use App\Models\SupplierContractBilling;
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
        $billing = $this->getOwnerRecord();
        
        // Verwende eine generische Konfiguration, da es keine spezielle forSupplierContractBillings gibt
        return (new DocumentUploadConfig([
            'title' => 'Abrechnungs-Dokumente',
            'sectionTitle' => 'Abrechnungs-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'invoice' => 'Rechnung',
                'credit_note' => 'Gutschrift',
                'statement' => 'Abrechnung',
                'correspondence' => 'Korrespondenz',
                'other' => 'Sonstiges',
            ],
        ]))
            ->setModel($billing)
            ->setAdditionalData([
                'billing_id' => $billing->id,
                'billing_number' => $billing->billing_number,
                'contract_number' => $billing->supplierContract?->contract_number,
                'supplier_name' => $billing->supplierContract?->supplier?->company_name,
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
}