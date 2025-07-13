<?php

namespace App\Services;

use App\Models\SolarPlant;
use App\Models\CompanySetting;

class MermaidChartService
{
    /**
     * Generiert einen Mermaid-Chart-Code für eine Solaranlage
     */
    public function generateSolarPlantChart(SolarPlant $solarPlant): string
    {
        $companySetting = CompanySetting::current();
        $template = $companySetting->mermaid_chart_template;
        
        if (empty($template)) {
            $template = $this->getDefaultTemplate();
        }
        
        // Lade alle benötigten Daten
        $solarPlant->load([
            'participations.customer',
            'suppliers',
            'supplierContracts.supplier',
            'activeSupplierContractAssignments.supplierContract.supplier'
        ]);
        
        $customers = $solarPlant->participations;
        $suppliers = $solarPlant->suppliers;
        $contracts = $solarPlant->supplierContracts;
        
        // Erstelle Platzhalter-Ersetzungen
        $replacements = [
            '{{plant_name}}' => $solarPlant->name,
            '{{plant_location}}' => $solarPlant->location,
            '{{plant_capacity}}' => number_format($solarPlant->total_capacity_kw, 2, ',', '.') . ' kWp',
            '{{plant_status}}' => $this->getStatusLabel($solarPlant->status),
            '{{customers}}' => $this->generateCustomersSection($customers),
            '{{suppliers}}' => $this->generateSuppliersSection($suppliers),
            '{{contracts}}' => $this->generateContractsSection($contracts),
            '{{customer_connections}}' => $this->generateCustomerConnections($customers),
            '{{supplier_connections}}' => $this->generateSupplierConnections($suppliers, $contracts),
            '{{billing_connections}}' => $this->generateBillingConnections($customers, $contracts),
        ];
        
        // Ersetze Platzhalter im Template
        $chartCode = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        return $chartCode;
    }
    
    /**
     * Standard-Template für Mermaid-Charts
     */
    private function getDefaultTemplate(): string
    {
        return 'flowchart TD
    %% Styling
    classDef solarPlant fill:#FFD700,stroke:#FF8C00,stroke-width:3px,color:#000,font-weight:bold
    classDef customer fill:#87CEEB,stroke:#4682B4,stroke-width:2px,color:#000
    classDef supplier fill:#98FB98,stroke:#32CD32,stroke-width:2px,color:#000
    classDef contract fill:#DDA0DD,stroke:#9370DB,stroke-width:1.5px,color:#000
    classDef money fill:#F0E68C,stroke:#DAA520,stroke-width:1.5px,color:#000
    classDef info fill:#FFF,stroke:#999,stroke-width:1px,color:#333

    %% Solaranlage
    SA["Solaranlage<br/>{{plant_name}}<br/>{{plant_capacity}}"]:::solarPlant

    {{customers}}

    {{suppliers}}

    {{contracts}}

    {{customer_connections}}

    {{supplier_connections}}

    {{billing_connections}}

    %% Hinweis
    Info["**Hinweise:**<br/>
    - Alle Kosten/Erlöse werden anteilig verteilt.<br/>
    - Alle Lieferanten und Dienstleister sind berücksichtigt."]:::info';
    }
    
    /**
     * Generiert die Kunden-Sektion
     */
    private function generateCustomersSection($customers): string
    {
        if ($customers->isEmpty()) {
            return '%% Keine Kunden';
        }
        
        $customerNodes = [];
        $customerCircle = 'KundenKreis(((Beteiligte)))';
        $customerConnections = [];
        
        foreach ($customers as $index => $participation) {
            $customer = $participation->customer;
            if (!$customer) continue;
            
            $customerName = $customer->customer_type === 'business' 
                ? ($customer->company_name ?: $customer->name)
                : $customer->name;
            
            $percentage = number_format($participation->percentage, 0) . '%';
            $nodeId = 'K' . ($index + 1);
            
            $customerNodes[] = "{$nodeId}[\"{$customerName}<br/>{$percentage}\"]:::customer";
            $customerConnections[] = "{$customerCircle} -.->|{$percentage}| {$nodeId}";
        }
        
        $result = implode("\n    ", $customerNodes);
        $result .= "\n    " . $customerCircle;
        $result .= "\n    " . implode("\n    ", $customerConnections);
        
        return $result;
    }
    
    /**
     * Generiert die Lieferanten-Sektion
     */
    private function generateSuppliersSection($suppliers): string
    {
        if ($suppliers->isEmpty()) {
            return '%% Keine Lieferanten';
        }
        
        $supplierNodes = [];
        
        foreach ($suppliers as $index => $supplier) {
            $nodeId = 'L' . ($index + 1);
            $role = $supplier->pivot->role ?? 'Dienstleister';
            
            $supplierNodes[] = "{$nodeId}[\"{$supplier->name}<br/>{$role}\"]:::supplier";
        }
        
        return implode("\n    ", $supplierNodes);
    }
    
    /**
     * Generiert die Verträge-Sektion
     */
    private function generateContractsSection($contracts): string
    {
        if ($contracts->isEmpty()) {
            return '%% Keine Verträge';
        }
        
        $contractNodes = [];
        
        foreach ($contracts as $index => $contract) {
            $nodeId = 'V' . ($index + 1);
            $title = $contract->title ?: 'Vertrag';
            $supplier = $contract->supplier ? $contract->supplier->name : 'Unbekannt';
            
            $contractNodes[] = "{$nodeId}[\"{$title}<br/>({$supplier})\"]:::contract";
        }
        
        return implode("\n    ", $contractNodes);
    }
    
    /**
     * Generiert Kunden-Verbindungen
     */
    private function generateCustomerConnections($customers): string
    {
        if ($customers->isEmpty()) {
            return '%% Keine Kunden-Verbindungen';
        }
        
        $connections = ['KundenKreis -->|Investition| SA'];
        $connections[] = 'SA -.->|Ertragsbeteiligung| KundenKreis';
        
        return implode("\n    ", $connections);
    }
    
    /**
     * Generiert Lieferanten-Verbindungen
     */
    private function generateSupplierConnections($suppliers, $contracts): string
    {
        $connections = [];
        
        // Lieferanten zu Verträgen
        foreach ($suppliers as $supplierIndex => $supplier) {
            foreach ($contracts as $contractIndex => $contract) {
                if ($contract->supplier_id === $supplier->id) {
                    $supplierNodeId = 'L' . ($supplierIndex + 1);
                    $contractNodeId = 'V' . ($contractIndex + 1);
                    $connections[] = "{$supplierNodeId} --> {$contractNodeId}";
                }
            }
        }
        
        // Verträge zur Solaranlage
        foreach ($contracts as $contractIndex => $contract) {
            $contractNodeId = 'V' . ($contractIndex + 1);
            $connections[] = "{$contractNodeId} ---> SA";
        }
        
        return empty($connections) ? '%% Keine Lieferanten-Verbindungen' : implode("\n    ", $connections);
    }
    
    /**
     * Generiert Abrechnungs-Verbindungen
     */
    private function generateBillingConnections($customers, $contracts): string
    {
        if ($customers->isEmpty()) {
            return '%% Keine Abrechnungs-Verbindungen';
        }
        
        $connections = [];
        
        // Beispiel-Abrechnungen für bekannte Lieferanten
        $billingNodes = [];
        $billingConnections = [];
        
        foreach ($contracts as $contractIndex => $contract) {
            $supplier = $contract->supplier;
            if (!$supplier) continue;
            
            $supplierName = $supplier->name;
            
            // Spezielle Behandlung für bekannte Lieferanten
            if (str_contains(strtolower($supplierName), 'next')) {
                $billingNodes[] = 'DMRech["Rechnung Direktvermarktung<br/>NEXT"]:::money';
                $billingNodes[] = 'DMGut["Gutschrift Direktvermarktung<br/>NEXT"]:::money';
                
                $supplierNodeId = 'L' . ($contractIndex + 1);
                $billingConnections[] = "{$supplierNodeId} --> DMRech";
                $billingConnections[] = "{$supplierNodeId} --> DMGut";
                
                // Verbindungen zu Kunden
                foreach ($customers as $customerIndex => $participation) {
                    $customerNodeId = 'K' . ($customerIndex + 1);
                    $percentage = number_format($participation->percentage, 0) . '%';
                    $billingConnections[] = "DMRech -.->|\"{$percentage}\"| {$customerNodeId}";
                    $billingConnections[] = "DMGut -.->|\"{$percentage}\"| {$customerNodeId}";
                }
            }
            
            if (str_contains(strtolower($supplierName), 'ewe') || str_contains(strtolower($supplierName), 'exe')) {
                $billingNodes[] = 'MSBRech["Messstellenbetrieb<br/>' . $supplierName . '"]:::money';
                $billingNodes[] = 'MSGut["Energie-Gutschrift<br/>' . $supplierName . '"]:::money';
                
                $supplierNodeId = 'L' . ($contractIndex + 1);
                $billingConnections[] = "{$supplierNodeId} --> MSBRech";
                $billingConnections[] = "{$supplierNodeId} --> MSGut";
                
                // Verbindungen zu Kunden
                foreach ($customers as $customerIndex => $participation) {
                    $customerNodeId = 'K' . ($customerIndex + 1);
                    $percentage = number_format($participation->percentage, 0) . '%';
                    $billingConnections[] = "MSBRech -.->|\"{$percentage}\"| {$customerNodeId}";
                    $billingConnections[] = "MSGut -.->|\"{$percentage}\"| {$customerNodeId}";
                }
            }
        }
        
        $result = '';
        if (!empty($billingNodes)) {
            $result .= "\n    %% Abrechnungen\n    ";
            $result .= implode("\n    ", $billingNodes);
        }
        
        if (!empty($billingConnections)) {
            $result .= "\n    %% Abrechnungs-Verbindungen\n    ";
            $result .= implode("\n    ", $billingConnections);
        }
        
        return $result ?: '%% Keine Abrechnungs-Verbindungen';
    }
    
    /**
     * Konvertiert Status-Codes zu lesbaren Labels
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'in_planning' => 'In Planung',
            'planned' => 'Geplant',
            'under_construction' => 'Im Bau',
            'awaiting_commissioning' => 'Warte auf Inbetriebnahme',
            'active' => 'Aktiv',
            'maintenance' => 'Wartung',
            'inactive' => 'Inaktiv',
            default => $status,
        };
    }
}