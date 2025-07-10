<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF-Analyse Ergebnisse - {{ $uploadedPdf->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">PDF-Analyse Ergebnisse</h1>
                    <p class="text-gray-600 mt-2">{{ $uploadedPdf->name }}</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('uploaded-pdfs.view-pdf', $uploadedPdf) }}"
                       target="_blank"
                       class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        PDF anzeigen
                    </a>
                    <a href="{{ route('uploaded-pdfs.download', $uploadedPdf) }}"
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        PDF herunterladen
                    </a>
                    <a href="{{ url()->previous() }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        ← Zurück
                    </a>
                </div>
            </div>
        </div>

        @if(isset($error))
            <!-- Fehler-Anzeige -->
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong class="font-bold">Fehler!</strong>
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @else
            <!-- Datei-Informationen -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Datei-Informationen</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Dateiname</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $uploadedPdf->original_filename }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Dateigröße</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $uploadedPdf->formatted_size }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Hochgeladen von</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $uploadedPdf->uploadedBy->name ?? 'Unbekannt' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Analysiert am</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $uploadedPdf->analysis_completed_at?->format('d.m.Y H:i:s') ?? 'Gerade eben' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">ZUGFeRD-kompatibel</p>
                        @if(isset($analysisData['raw_xml']) && !empty($analysisData['raw_xml']))
                            <p class="mt-1 text-sm text-green-600 font-semibold flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Ja
                            </p>
                        @else
                            <p class="mt-1 text-sm text-red-600 font-semibold flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Nein
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Dokumententyp</p>
                        <p class="mt-1 text-sm text-gray-900 font-semibold">
                            {{ $analysisData['document_type'] ?? 'Unbekannt' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Analyse-Qualität -->
            @if(isset($analysisData['overall_confidence']))
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Analyse-Qualität</h2>
                
                @php
                    // Berechne Contract-Match-Score falls vorhanden
                    $contractMatchScore = 0;
                    if (isset($analysisData['contract_matching']) && !empty($analysisData['contract_matching'])) {
                        $bestContractMatch = null;
                        $highestContractConfidence = 0;
                        foreach($analysisData['contract_matching'] as $match) {
                            if($match['confidence'] > $highestContractConfidence) {
                                $highestContractConfidence = $match['confidence'];
                                $bestContractMatch = $match;
                            }
                        }
                        $contractMatchScore = $bestContractMatch ? round($bestContractMatch['confidence'] * 100) : 0;
                    }
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $analysisData['overall_confidence']['overall_score'] >= 80 ? 'text-green-600' : ($analysisData['overall_confidence']['overall_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $analysisData['overall_confidence']['overall_score'] }}%
                        </div>
                        <p class="text-sm text-gray-600">Gesamt-Score</p>
                        <p class="text-xs font-medium {{ $analysisData['overall_confidence']['overall_score'] >= 80 ? 'text-green-600' : ($analysisData['overall_confidence']['overall_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $analysisData['overall_confidence']['quality_level'] }}
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-semibold text-blue-600">{{ round($analysisData['overall_confidence']['invoice_data_score']) }}%</div>
                        <p class="text-sm text-gray-600">Rechnungsdaten</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-semibold text-purple-600">{{ round($analysisData['overall_confidence']['supplier_data_score']) }}%</div>
                        <p class="text-sm text-gray-600">Lieferantendaten</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-semibold text-orange-600">{{ round($analysisData['overall_confidence']['matching_score']) }}%</div>
                        <p class="text-sm text-gray-600">Lieferanten-Match</p>
                    </div>
                    @if($contractMatchScore > 0)
                        <div class="text-center">
                            <div class="text-xl font-semibold text-green-600">{{ $contractMatchScore }}%</div>
                            <p class="text-sm text-gray-600">Vertrags-Match</p>
                        </div>
                    @else
                        <div class="text-center opacity-50">
                            <div class="text-xl font-semibold text-gray-400">--</div>
                            <p class="text-sm text-gray-400">Vertrags-Match</p>
                            <p class="text-xs text-gray-400">Nicht verfügbar</p>
                        </div>
                    @endif
                </div>
                @if(!empty($analysisData['overall_confidence']['quality_indicators']))
                    <div class="border-t pt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Qualitätsindikatoren:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($analysisData['overall_confidence']['quality_indicators'] as $indicator)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ {{ $indicator }}
                                </span>
                            @endforeach
                            @if($contractMatchScore >= 50)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Vertrag erfolgreich zugeordnet
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            @endif

            <!-- Verwendete Analyse-Regeln -->
            @if(isset($analysisData['used_rules_and_patterns']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Verwendete Analyse-Regeln & Pattern</h2>
                    <div class="space-y-4">
                        <!-- Erkennungspattern -->
                        <div>
                            <h3 class="font-semibold text-gray-800">Erkennungspattern</h3>
                            @if(!empty($analysisData['used_rules_and_patterns']['recognition_pattern_ids']))
                                <p class="text-sm text-gray-600">IDs: {{ implode(', ', $analysisData['used_rules_and_patterns']['recognition_pattern_ids']) }}</p>
                            @else
                                <p class="text-sm text-gray-500">Keine spezifischen Erkennungspattern verwendet.</p>
                            @endif
                        </div>
                        <!-- Extraktionsregeln -->
                        <div>
                            <h3 class="font-semibold text-gray-800">Extraktionsregeln</h3>
                            @if(!empty($analysisData['used_rules_and_patterns']['extraction_rule_ids']))
                                <p class="text-sm text-gray-600">IDs: {{ implode(', ', $analysisData['used_rules_and_patterns']['extraction_rule_ids']) }}</p>
                            @else
                                <p class="text-sm text-gray-500">Keine spezifischen Extraktionsregeln verwendet.</p>
                            @endif
                        </div>
                        <!-- Vertrags-Matching-Regeln -->
                        <div>
                            <h3 class="font-semibold text-gray-800">Vertrags-Matching-Regeln</h3>
                            @if(!empty($analysisData['used_rules_and_patterns']['contract_matching_rule_ids']))
                                <p class="text-sm text-gray-600">IDs: {{ implode(', ', $analysisData['used_rules_and_patterns']['contract_matching_rule_ids']) }}</p>
                            @else
                                <p class="text-sm text-gray-500">Keine spezifischen Vertrags-Matching-Regeln verwendet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($analysisData))
            @php
                // Debug-Logs für anteilige Kosten Problem
                \Log::info('PDF Analysis Data Structure Debug - Anteilige Kosten', [
                    'has_contract_matching' => isset($analysisData['contract_matching']),
                    'contract_matching_count' => isset($analysisData['contract_matching']) ? count($analysisData['contract_matching']) : 0,
                    'has_supplier_data' => isset($analysisData['supplier_data']),
                    'has_recognized_supplier' => isset($analysisData['recognized_supplier']),
                    'has_invoice_data' => isset($analysisData['invoice_data']),
                    'has_total_amount' => isset($analysisData['invoice_data']['total_amount']),
                    'total_amount_raw' => $analysisData['invoice_data']['total_amount'] ?? 'N/A',
                    'analysis_data_keys' => array_keys($analysisData),
                ]);
                
                if (isset($analysisData['contract_matching']) && !empty($analysisData['contract_matching'])) {
                    foreach($analysisData['contract_matching'] as $index => $match) {
                        \Log::info("Contract Match Debug #{$index} - Anteilige Kosten", [
                            'confidence' => $match['confidence'] ?? 'N/A',
                            'has_contract_id' => isset($match['contract']['id']),
                            'contract_id' => $match['contract']['id'] ?? 'N/A',
                            'contract_number' => $match['contract']['contract_number'] ?? 'N/A',
                            'confidence_sufficient' => ($match['confidence'] ?? 0) >= 0.5,
                        ]);
                    }
                }
                
                // Debug für Gesamtbetrag-Berechnung
                $totalAmountDebug = 0;
                if (isset($analysisData['invoice_data']['total_amount']) && !empty($analysisData['invoice_data']['total_amount'])) {
                    $totalAmountRaw = $analysisData['invoice_data']['total_amount'];
                    $cleaned = preg_replace('/[€$£¥\s]/', '', $totalAmountRaw);
                    
                    if (is_numeric($cleaned)) {
                        $totalAmountDebug = floatval($cleaned);
                    } else {
                        if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $cleaned)) {
                            $totalAmountDebug = floatval(str_replace(['.', ','], ['', '.'], $cleaned));
                        } elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $cleaned)) {
                            $totalAmountDebug = floatval(str_replace(',', '', $cleaned));
                        } else {
                            $totalAmountDebug = floatval(str_replace(',', '.', $cleaned));
                        }
                    }
                }
                
                \Log::info('Total Amount Debug - Anteilige Kosten', [
                    'raw_total_amount' => $analysisData['invoice_data']['total_amount'] ?? 'N/A',
                    'calculated_total_amount' => $totalAmountDebug,
                    'has_valid_amount' => $totalAmountDebug > 0,
                ]);
            @endphp
                
                <!-- Rechnungsdaten -->
                @if(isset($analysisData['invoice_data']) && !empty($analysisData['invoice_data']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Erkannte Rechnungsdaten</h2>
                    
                    @php
                        $invoiceData = $analysisData['invoice_data'];
                        
                        // Funktion für deutsche Datumsformatierung
                        $formatGermanDate = function($date) {
                            if (empty($date)) return $date;
                            // Versuche verschiedene Datumsformate zu parsen
                            $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'Y-m-d H:i:s'];
                            foreach ($formats as $format) {
                                $dateObj = DateTime::createFromFormat($format, $date);
                                if ($dateObj !== false) {
                                    return $dateObj->format('d.m.Y');
                                }
                            }
                            return $date; // Fallback: Original zurückgeben
                        };
                        
                        // Funktion für deutsche Geldbeträge
                        $formatGermanAmount = function($amount) {
                            if (empty($amount)) return $amount;
                            // Entferne Währungssymbole und normalisiere
                            $cleaned = preg_replace('/[€$£¥\s]/', '', $amount);
                            
                            // Behandle verschiedene Zahlenformate
                            if (is_numeric($cleaned)) {
                                // Einfache Zahl ohne Formatierung
                                $number = floatval($cleaned);
                            } else {
                                // Behandle deutsche Zahlenformate (1.234,56) und englische (1,234.56)
                                if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $cleaned)) {
                                    // Deutsches Format: 1.234,56
                                    $number = floatval(str_replace(['.', ','], ['', '.'], $cleaned));
                                } elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $cleaned)) {
                                    // Englisches Format: 1,234.56
                                    $number = floatval(str_replace(',', '', $cleaned));
                                } else {
                                    // Fallback: Versuche direkte Konvertierung
                                    $number = floatval(str_replace(',', '.', $cleaned));
                                }
                            }
                            
                            return number_format($number, 2, ',', '.') . ' €';
                        };
                        
                        // Definiere die Anzeigereihenfolge
                        $fieldOrder = [
                            'invoice_number' => 'Rechnungsnummer',
                            'contract_account' => 'Vertragskonto',
                            'invoice_date' => 'Rechnungsdatum',
                            'customer_number' => 'Kunde',
                            'period_start' => 'Periode Start',
                            'period_end' => 'Periode Ende',
                            'consumption_site' => 'Verbrauchsstelle'
                        ];
                        
                        // Beträge separat am Ende
                        $amountFields = [
                            'net_amount' => 'Nettobetrag',
                            'tax_amount' => 'MwSt-Betrag',
                            'total_amount' => 'Gesamtbetrag'
                        ];
                    @endphp
                    
                    <!-- Hauptdaten -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        @foreach($fieldOrder as $key => $label)
                            @if(isset($invoiceData[$key]))
                                <div>
                                    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                                    <p class="mt-1 text-sm text-gray-900 font-mono">
                                        @if($key === 'invoice_date')
                                            {{ $formatGermanDate($invoiceData[$key]) }}
                                        @else
                                            {{ $invoiceData[$key] }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endforeach
                        
                        <!-- Andere Felder die nicht in der definierten Reihenfolge sind -->
                        @foreach($invoiceData as $key => $value)
                            @if(!array_key_exists($key, $fieldOrder) && !array_key_exists($key, $amountFields))
                                <div>
                                    <p class="text-sm font-medium text-gray-500">
                                        {{ match($key) {
                                            'creditor_number' => 'Gläubiger-Nr.',
                                            'contract_number' => 'Vertragsnummer',
                                            'due_date' => 'Fälligkeitsdatum',
                                            'tax_rate' => 'MwSt-Satz',
                                            default => ucfirst(str_replace('_', ' ', $key))
                                        } }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-900 font-mono">
                                        @if(str_contains($key, 'date'))
                                            {{ $formatGermanDate($value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    
                    <!-- Beträge in separater Zeile -->
                    @php
                        $hasAmounts = false;
                        foreach($amountFields as $key => $label) {
                            if(isset($invoiceData[$key]) && !empty($invoiceData[$key])) {
                                $hasAmounts = true;
                                break;
                            }
                        }
                    @endphp
                    
                    @if($hasAmounts)
                        <div class="border-t pt-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Beträge</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach($amountFields as $key => $label)
                                    @if(isset($invoiceData[$key]) && !empty($invoiceData[$key]))
                                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                                            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                                {{ $formatGermanAmount($invoiceData[$key]) }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="text-center p-3 bg-gray-100 rounded-lg opacity-50">
                                            <p class="text-sm font-medium text-gray-400">{{ $label }}</p>
                                            <p class="mt-1 text-sm text-gray-400">Nicht verfügbar</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Erkannte Solaranlagenzuordnung -->
                @if(isset($analysisData['contract_matching']) && !empty($analysisData['contract_matching']))
                    @php
                        // Finde den besten Contract-Match (höchste Confidence)
                        $bestMatch = null;
                        $highestConfidence = 0;
                        foreach($analysisData['contract_matching'] as $match) {
                            if($match['confidence'] > $highestConfidence) {
                                $highestConfidence = $match['confidence'];
                                $bestMatch = $match;
                            }
                        }
                    @endphp
                    
                    @if($bestMatch && $bestMatch['confidence'] >= 0.5 && isset($bestMatch['contract']['id']))
                        @php
                            // Debug-Logs für Solaranlagen-Zuordnung
                            \Log::info('Solar Plant Assignment Debug - Best Match Found', [
                                'contract_id' => $bestMatch['contract']['id'],
                                'confidence' => $bestMatch['confidence'],
                                'contract_number' => $bestMatch['contract']['contract_number'] ?? 'N/A',
                            ]);
                            
                            // Lade Solaranlagen-Zuordnungen für den erkannten Vertrag
                            $contract = \App\Models\SupplierContract::with([
                                'activeSolarPlantAssignments.solarPlant.participations.customer'
                            ])->find($bestMatch['contract']['id']);
                            
                            \Log::info('Solar Plant Assignment Debug - Contract Loaded', [
                                'contract_found' => $contract !== null,
                                'contract_id' => $contract ? $contract->id : 'N/A',
                                'has_active_assignments' => $contract ? $contract->activeSolarPlantAssignments->isNotEmpty() : false,
                                'assignment_count' => $contract ? $contract->activeSolarPlantAssignments->count() : 0,
                            ]);
                            
                            $solarPlantAssignments = $contract ? $contract->activeSolarPlantAssignments : collect();
                            
                            // Ermittle Gesamtbetrag aus Rechnungsdaten für Betragsaufteilung
                            $totalAmount = 0;
                            if (isset($analysisData['invoice_data']['total_amount']) && !empty($analysisData['invoice_data']['total_amount'])) {
                                $totalAmountRaw = $analysisData['invoice_data']['total_amount'];
                                // Bereinige den Betrag (entferne Währungssymbole und normalisiere)
                                $cleaned = preg_replace('/[€$£¥\s]/', '', $totalAmountRaw);
                                
                                if (is_numeric($cleaned)) {
                                    $totalAmount = floatval($cleaned);
                                } else {
                                    // Behandle deutsche Zahlenformate (1.234,56) und englische (1,234.56)
                                    if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $cleaned)) {
                                        // Deutsches Format: 1.234,56
                                        $totalAmount = floatval(str_replace(['.', ','], ['', '.'], $cleaned));
                                    } elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $cleaned)) {
                                        // Englisches Format: 1,234.56
                                        $totalAmount = floatval(str_replace(',', '', $cleaned));
                                    } else {
                                        // Fallback: Versuche direkte Konvertierung
                                        $totalAmount = floatval(str_replace(',', '.', $cleaned));
                                    }
                                }
                            }
                            
                            // Funktion für deutsche Geldbeträge
                            $formatGermanAmount = function($amount) {
                                if (empty($amount) || $amount == 0) return '0,00 €';
                                return number_format($amount, 2, ',', '.') . ' €';
                            };
                        @endphp
                        
                        @if($solarPlantAssignments->isNotEmpty())
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Erkannte Solaranlagenzuordnung</h2>
                            
                            <!-- Übersicht -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-blue-800">
                                            {{ $solarPlantAssignments->count() }} Solaranlage{{ $solarPlantAssignments->count() !== 1 ? 'n' : '' }} zugeordnet
                                        </h3>
                                        <p class="text-sm text-blue-600">
                                            Gesamtprozentsatz: {{ number_format($contract->total_solar_plant_percentage, 2, ',', '.') }}%
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-blue-600">
                                            Verfügbar: {{ number_format($contract->available_solar_plant_percentage, 2, ',', '.') }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Solaranlagen-Details -->
                            <div class="space-y-6">
                                @foreach($solarPlantAssignments as $assignment)
                                    @php $plant = $assignment->solarPlant; @endphp
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <!-- Solaranlagen-Header -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 class="text-lg font-semibold text-gray-900">
                                                    {{ $plant->plant_number }} - {{ $plant->name }}
                                                </h4>
                                                <p class="text-sm text-gray-600">
                                                    📍 {{ $plant->location }} | ⚡ {{ number_format($plant->total_capacity_kw, 2, ',', '.') }} kW
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <a href="{{ url('/admin/solar-plants/' . $plant->id) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                    Solaranlage öffnen
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Vertragsprozentsatz -->
                                        <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-green-800">Vertragsprozentsatz:</span>
                                                <span class="text-lg font-semibold text-green-900">{{ $assignment->formatted_percentage }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Kostenträger-Beteiligungen -->
                                        @php
                                            $participations = $plant->participations->sortByDesc('percentage');
                                        @endphp
                                        @if($participations->isNotEmpty())
                                            <div>
                                                <h5 class="text-md font-semibold text-gray-800 mb-3">
                                                    👥 Kostenträger-Beteiligungen ({{ $participations->count() }})
                                                </h5>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($participations as $participation)
                                                        @php
                                                            $customer = $participation->customer;
                                                            
                                                            // Berechne anteiligen Betrag für diesen Kostenträger
                                                            // Formel: Gesamtbetrag × Anlagenprozentsatz × Kostenträger-Prozentsatz / 10000
                                                            $participationAmount = 0;
                                                            if ($totalAmount > 0) {
                                                                $plantPercentage = $assignment->percentage ?? 100; // Anlagenprozentsatz
                                                                $customerPercentage = $participation->percentage ?? 0; // Kostenträger-Prozentsatz
                                                                $participationAmount = ($totalAmount * $plantPercentage * $customerPercentage) / 10000;
                                                            }
                                                        @endphp
                                                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                            @php
                                                                // Debug-Logs für Anteilige Kosten Layout-Problem
                                                                \Log::info('Anteilige Kosten Layout Debug', [
                                                                    'customer_name' => $customer->display_name,
                                                                    'customer_number' => $customer->customer_number,
                                                                    'participation_percentage' => $participation->percentage,
                                                                    'total_amount' => $totalAmount,
                                                                    'participation_amount' => $participationAmount,
                                                                    'container_classes' => 'bg-gray-50 border border-gray-200 rounded-lg p-3',
                                                                    'flex_container_classes' => 'flex items-start justify-between mb-2',
                                                                    'right_container_classes' => 'text-right',
                                                                    'space_container_classes' => 'space-y-1',
                                                                    'problem' => 'Anteilige Kosten werden oben statt unten angezeigt',
                                                                    'expected_position' => 'rechts unten im Kasten',
                                                                    'current_position' => 'rechts oben im Kasten',
                                                                    'current_flex_alignment' => 'items-start (verursacht oben-Ausrichtung)',
                                                                    'needed_flex_alignment' => 'items-end oder items-stretch mit self-end'
                                                                ]);
                                                            @endphp
                                                            <div class="flex items-start justify-between mb-2">
                                                                <div>
                                                                    <h6 class="font-semibold text-gray-900">{{ $customer->display_name }}</h6>
                                                                    <p class="text-xs text-gray-600">{{ $customer->customer_number }}</p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                                        {{ $participation->formatted_percentage }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="text-xs text-gray-600 space-y-1">
                                                                @if($customer->email)
                                                                    <div>📧 {{ $customer->email }}</div>
                                                                @endif
                                                                @if($customer->city)
                                                                    <div>📍 {{ $customer->city }}</div>
                                                                @endif
                                                                @if($customer->customer_type)
                                                                    <div>🏢 {{ $customer->customer_type === 'business' ? 'Geschäftskunde' : 'Privatkunde' }}</div>
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="mt-2 flex items-end justify-between">
                                                                <a href="{{ url('/admin/customers/' . $customer->id) }}"
                                                                   target="_blank"
                                                                   class="inline-flex items-center px-2 py-1 border border-blue-300 shadow-sm text-xs leading-4 font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-blue-500">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                                    </svg>
                                                                    Kunde öffnen
                                                                </a>
                                                                
                                                                @if($totalAmount > 0)
                                                                    <div class="text-right">
                                                                        <p class="text-xs text-gray-500 mb-1">Anteilige Kosten</p>
                                                                        <p class="text-lg font-bold text-green-600">
                                                                            {{ $formatGermanAmount($participationAmount) }}
                                                                        </p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                                <!-- Beteiligungsstatistiken -->
                                                <div class="mt-4 pt-3 border-t border-gray-200">
                                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <span class="font-medium text-gray-700">Gesamt-Beteiligung:</span>
                                                            <span class="ml-2 font-semibold {{ $plant->total_participation == 100 ? 'text-green-600' : 'text-yellow-600' }}">
                                                                {{ number_format($plant->total_participation, 2, ',', '.') }}%
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="font-medium text-gray-700">Verfügbar:</span>
                                                            <span class="ml-2 font-semibold {{ $plant->available_participation == 0 ? 'text-green-600' : 'text-blue-600' }}">
                                                                {{ number_format($plant->available_participation, 2, ',', '.') }}%
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Keine Kostenträger -->
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    <div>
                                                        <h6 class="text-sm font-medium text-yellow-800">Keine Kostenträger-Beteiligungen</h6>
                                                        <p class="text-sm text-yellow-700 mt-1">
                                                            Für diese Solaranlage sind noch keine Kostenträger-Beteiligungen hinterlegt.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endif
                @endif

                <!-- Lieferantendaten -->
                @if(isset($analysisData['supplier_data']) && !empty($analysisData['supplier_data']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Erkannte Lieferantendaten</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($analysisData['supplier_data'] as $key => $value)
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ match($key) {
                                        'company_name' => 'Firmenname',
                                        'email' => 'E-Mail',
                                        'phone' => 'Telefon',
                                        'address' => 'Adresse',
                                        'consumption_site' => 'Verbrauchsstelle',
                                        'market_location' => 'Marktlokation',
                                        'network_operator' => 'Netzbetreiber',
                                        'network_operator_code' => 'Netzbetreiber-Code',
                                        'metering_operator' => 'Messstellenbetreiber',
                                        'meter_number' => 'Zählernummer',
                                        'vat_id' => 'USt-ID',
                                        'commercial_register' => 'Handelsregister',
                                        default => ucfirst(str_replace('_', ' ', $key))
                                    } }}
                                </p>
                                <p class="mt-1 text-sm text-gray-900">
                                    @if(is_array($value))
                                        @if($key === 'address')
                                            {{ $value['full_address'] ?? implode(', ', array_filter($value)) }}
                                        @else
                                            {{ implode(', ', array_filter($value)) }}
                                        @endif
                                    @else
                                        {{ $value }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Erkannte Vertragsdaten -->
                @if(isset($analysisData['contract_matching']) && !empty($analysisData['contract_matching']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Erkannte Vertragsdaten</h2>
                    
                    @php
                        // Finde den besten Contract-Match (höchste Confidence)
                        $bestMatch = null;
                        $highestConfidence = 0;
                        foreach($analysisData['contract_matching'] as $match) {
                            if($match['confidence'] > $highestConfidence) {
                                $highestConfidence = $match['confidence'];
                                $bestMatch = $match;
                            }
                        }
                    @endphp
                    
                    @if($bestMatch && $bestMatch['confidence'] >= 0.5)
                        @php $contract = $bestMatch['contract']; @endphp
                        
                        <!-- Haupt-Vertragsinformationen -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-green-800">
                                        {{ $contract['contract_number'] ?? 'Unbekannte Vertragsnummer' }}
                                    </h3>
                                    <p class="text-sm text-green-600">
                                        Zuordnungs-Confidence:
                                        <span class="font-semibold">{{ round($bestMatch['confidence'] * 100, 1) }}%</span>
                                    </p>
                                </div>
                                <div>
                                    @if(isset($contract['id']))
                                        <a href="{{ url('/admin/supplier-contracts/' . $contract['id']) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            Vertrag öffnen
                                        </a>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Vertragsdaten Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @if(isset($contract['title']))
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Vertragsbezeichnung</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $contract['title'] }}</p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['external_contract_number']))
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Externe Vertragsnummer</p>
                                        <p class="mt-1 text-sm text-gray-900 font-mono">{{ $contract['external_contract_number'] }}</p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['contract_recognition_1']))
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Verbrauchsstelle</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $contract['contract_recognition_1'] }}</p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['start_date']))
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Vertragsbeginn</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($contract['start_date'])->format('d.m.Y') }}
                                        </p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['end_date']) && $contract['end_date'])
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Vertragsende</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($contract['end_date'])->format('d.m.Y') }}
                                        </p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['status']))
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Status</p>
                                        <p class="mt-1 text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $contract['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $contract['status'] === 'active' ? 'Aktiv' : ucfirst($contract['status']) }}
                                            </span>
                                        </p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['contract_value']) && $contract['contract_value'])
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Vertragswert</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ number_format($contract['contract_value'], 2, ',', '.') }} {{ $contract['currency'] ?? 'EUR' }}
                                        </p>
                                    </div>
                                @endif
                                
                                @if(isset($contract['description']) && $contract['description'])
                                    <div class="md:col-span-2 lg:col-span-3">
                                        <p class="text-sm font-medium text-gray-500">Beschreibung</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $contract['description'] }}</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Matching-Details -->
                            @if(isset($bestMatch['matching_fields']) && !empty($bestMatch['matching_fields']))
                                <div class="mt-4 pt-4 border-t border-green-200">
                                    <h4 class="text-sm font-medium text-green-800 mb-2">Übereinstimmende Datenfelder:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($bestMatch['matching_fields'] as $field)
                                            <div class="text-xs bg-white rounded px-2 py-1 border border-green-200">
                                                <span class="font-medium">{{ $field['field_source'] }}.{{ $field['field_name'] ?? 'text' }}:</span>
                                                <span class="text-gray-600">{{ $field['extracted_value'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Weitere Matches (falls vorhanden) -->
                        @if(count($analysisData['contract_matching']) > 1)
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Weitere mögliche Verträge:</h4>
                                <div class="space-y-2">
                                    @foreach($analysisData['contract_matching'] as $match)
                                        @if($match !== $bestMatch && $match['confidence'] >= 0.3)
                                            <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="text-sm font-medium">{{ $match['contract']['contract_number'] ?? 'N/A' }}</span>
                                                        <span class="text-xs text-gray-500 ml-2">
                                                            {{ round($match['confidence'] * 100, 1) }}% Übereinstimmung
                                                        </span>
                                                    </div>
                                                    @if(isset($match['contract']['id']))
                                                        <a href="{{ url('/admin/supplier-contracts/' . $match['contract']['id']) }}"
                                                           target="_blank"
                                                           class="text-xs text-blue-600 hover:text-blue-800">
                                                            Ansehen →
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                    @else
                        <!-- Kein ausreichender Match -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-yellow-800">Keine eindeutige Vertragszuordnung</h3>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Es konnte kein Vertrag mit ausreichender Confidence (≥50%) zugeordnet werden.
                                        @if($bestMatch)
                                            Beste Übereinstimmung: {{ round($bestMatch['confidence'] * 100, 1) }}%
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Lieferanten-Matching -->
                @if(isset($analysisData['matching_scores']) && !empty($analysisData['matching_scores']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Lieferanten-Zuordnung</h2>
                        @if(isset($analysisData['recognized_supplier']) && $analysisData['recognized_supplier'])
                            <a href="{{ url('/admin/suppliers/' . $analysisData['recognized_supplier']->id) }}"
                               target="_blank"
                               class="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Lieferant öffnen
                            </a>
                        @endif
                    </div>
                    <div class="space-y-4">
                        @foreach($analysisData['matching_scores'] as $match)
                            <div class="border rounded-lg p-4 {{ $match['score'] >= 50 ? 'border-green-300 bg-green-50' : 'border-yellow-300 bg-yellow-50' }}">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $match['supplier_name'] }}</h3>
                                        <p class="text-sm text-gray-600">Lieferanten-ID: {{ $match['supplier_id'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $match['score'] >= 50 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $match['score'] }}% Übereinstimmung
                                        </span>
                                    </div>
                                </div>
                                @if(!empty($match['matches']))
                                    <div class="mt-2">
                                        <p class="text-sm font-medium text-gray-700">Gefundene Übereinstimmungen:</p>
                                        <ul class="mt-1 text-sm text-gray-600">
                                            @foreach($match['matches'] as $matchDetail)
                                                <li>• {{ $matchDetail }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Service-basierte Analyse -->
                @if(isset($analysisData['service_based_extraction']) || isset($analysisData['contract_matching']) || isset($analysisData['recognized_supplier']))
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Service-basierte Analyse</h2>
                    
                    @if(isset($analysisData['recognized_supplier']))
                        @php $supplier = $analysisData['recognized_supplier']; @endphp
                        <div class="mb-4 p-3 bg-green-50 rounded">
                            <h4 class="font-semibold text-green-800">Erkannter Lieferant</h4>
                            <div class="text-sm mt-2">
                                <strong>VoltMaster Name:</strong> {{ $supplier['display_name'] ?? $supplier['company_name'] }}<br>
                                <strong>VoltMaster Lieferanten-ID:</strong> {{ $supplier['id'] }}<br>
                                @if(isset($supplier['email']))
                                    <strong>E-Mail:</strong> {{ $supplier['email'] }}<br>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($analysisData['service_based_extraction']))
                        @php $extraction = $analysisData['service_based_extraction']; @endphp
                        <div class="mt-4 p-3 bg-green-50 rounded">
                            <h4 class="font-semibold text-green-800">Regelbasierte Extraktion</h4>
                            
                            @if(isset($extraction['extracted_data']) && !empty($extraction['extracted_data']))
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($extraction['extracted_data'] as $field => $value)
                                        <div class="text-sm">
                                            <strong>{{ ucfirst(str_replace('_', ' ', $field)) }}:</strong>
                                            @if(is_array($value))
                                                {{ implode(', ', $value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                
                                @if(isset($extraction['overall_confidence']))
                                    <div class="mt-2 text-sm">
                                        <strong>Gesamt-Confidence:</strong>
                                        <span class="px-2 py-1 rounded {{ $extraction['overall_confidence'] > 0.7 ? 'bg-green-200 text-green-800' : ($extraction['overall_confidence'] > 0.4 ? 'bg-yellow-200 text-yellow-800' : 'bg-red-200 text-red-800') }}">
                                            {{ round($extraction['overall_confidence'] * 100, 1) }}%
                                        </span>
                                    </div>
                                @endif
                                
                                @if(isset($extraction['confidence_scores']) && !empty($extraction['confidence_scores']))
                                    <div class="mt-3">
                                        <h5 class="font-medium text-green-700">Feld-spezifische Confidence:</h5>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-1 text-xs mt-1">
                                            @foreach($extraction['confidence_scores'] as $field => $confidence)
                                                <div class="px-2 py-1 rounded {{ $confidence > 0.7 ? 'bg-green-200' : ($confidence > 0.4 ? 'bg-yellow-200' : 'bg-red-200') }}">
                                                    {{ $field }}: {{ round($confidence * 100) }}%
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @else
                                <p class="text-gray-600 text-sm mt-2">Keine Daten durch Regeln extrahiert</p>
                            @endif
                        </div>
                    @endif
                    
                    @if(isset($analysisData['contract_matching']) && !empty($analysisData['contract_matching']))
                        <div class="mt-4 p-3 bg-purple-50 rounded">
                            <h4 class="font-semibold text-purple-800">Vertragsmatching</h4>
                            @foreach($analysisData['contract_matching'] as $match)
                                <div class="mt-2 p-2 bg-white rounded border">
                                    <div class="text-sm">
                                        <strong>Vertrag:</strong> {{ $match['contract']['contract_number'] ?? 'N/A' }}<br>
                                        <strong>Confidence:</strong>
                                        <span class="px-2 py-1 rounded {{ $match['confidence'] > 0.7 ? 'bg-green-200 text-green-800' : ($match['confidence'] > 0.4 ? 'bg-yellow-200 text-yellow-800' : 'bg-red-200 text-red-800') }}">
                                            {{ round($match['confidence'] * 100, 1) }}%
                                        </span>
                                        
                                        @if(isset($match['matching_fields']) && !empty($match['matching_fields']))
                                            <div class="mt-1">
                                                <strong>Übereinstimmende Felder:</strong>
                                                <ul class="list-disc list-inside text-xs">
                                                    @foreach($match['matching_fields'] as $field)
                                                        <li>{{ $field['field_source'] }}.{{ $field['field_name'] ?? 'text' }}: {{ $field['extracted_value'] }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    @if(isset($analysisData['fallback_used']) && $analysisData['fallback_used'])
                        <div class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                            <h4 class="font-semibold text-yellow-800">Fallback verwendet</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Die Service-basierte Extraktion war nicht erfolgreich. Es wurden hardkodierte Pattern als Fallback verwendet.
                                Überprüfen Sie die Konfiguration der Extraktionsregeln im Admin-Bereich.
                            </p>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Extrahierter Text -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Extrahierter Text</h2>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-2">
                            Textlänge: {{ number_format($analysisData['text_length']) }} Zeichen
                        </p>
                        <div class="max-h-96 overflow-y-auto">
                            <pre class="text-sm text-gray-900 whitespace-pre-wrap font-mono">{{ $analysisData['text'] }}</pre>
                        </div>
                    </div>
                </div>

                <!-- ZUGFeRD Raw XML -->
                @if(isset($analysisData['raw_xml']) && !empty($analysisData['raw_xml']))
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">ZUGFeRD XML-Rohdaten</h2>
                    <div class="bg-gray-800 text-white rounded-lg p-4">
                        <div class="max-h-96 overflow-y-auto">
                            @php
                                $rawXml = $analysisData['raw_xml'];
                                $formattedXml = '';
                                try {
                                    $dom = new \DOMDocument();
                                    $dom->preserveWhiteSpace = false;
                                    $dom->formatOutput = true;
                                    if ($dom->loadXML($rawXml)) {
                                        $formattedXml = $dom->saveXML();
                                    } else {
                                        $formattedXml = $rawXml;
                                    }
                                } catch (\Exception $e) {
                                    $formattedXml = $rawXml;
                                }
                            @endphp
                            <pre class="text-sm whitespace-pre-wrap font-mono">{{ $formattedXml }}</pre>
                        </div>
                    </div>
                </div>
                @endif
            @endif
        @endif
    </div>
</body>
</html>
