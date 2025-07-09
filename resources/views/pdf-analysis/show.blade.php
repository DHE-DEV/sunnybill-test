<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF-Analyse: {{ $analysis['filename'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .analysis-section {
            @apply bg-white rounded-lg shadow-md p-6 mb-6;
        }
        .analysis-header {
            @apply text-xl font-semibold text-gray-800 mb-4 border-b pb-2;
        }
        .data-grid {
            @apply grid grid-cols-1 md:grid-cols-2 gap-4;
        }
        .data-item {
            @apply bg-gray-50 p-3 rounded;
        }
        .data-label {
            @apply font-medium text-gray-600 text-sm;
        }
        .data-value {
            @apply text-gray-900 mt-1;
        }
        .text-content {
            @apply bg-gray-50 p-4 rounded max-h-96 overflow-y-auto text-sm font-mono;
        }
        .structured-item {
            @apply bg-blue-50 border border-blue-200 p-2 rounded text-sm;
        }
        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .collapsible-content.expanded {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }
    </style>
    <script>
        function toggleSection(sectionId) {
            const content = document.getElementById(sectionId);
            const icon = document.getElementById(sectionId + '-icon');
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('expanded');
                icon.style.transform = 'rotate(180deg)';
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">PDF-Analyse</h1>
                    <p class="text-gray-600 mt-2">{{ $analysis['filename'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">E-Mail von:</p>
                    <p class="font-medium">{{ $email->from_string }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $email->gmail_date->format('d.m.Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Automatische Vertragserkennung -->
        @if(isset($analysis['contract_recognition']) && $analysis['contract_recognition']['has_matching_contracts'])
            <div class="bg-gradient-to-r from-cyan-50 to-blue-50 border-l-4 border-cyan-500 rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-cyan-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-2xl font-bold text-gray-800">Automatische Vertragserkennung</h2>
                        <p class="text-gray-600 mt-1">{{ $analysis['contract_recognition']['total_matches'] }} passende(r) Vertrag/Verträge gefunden</p>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-cyan-100 text-cyan-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $analysis['contract_recognition']['total_matches'] }} Treffer
                        </div>
                    </div>
                </div>

                <!-- Suchkriterien -->
                @if(!empty($analysis['contract_recognition']['search_criteria']))
                    <div class="mb-6 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
                        <h4 class="font-semibold text-cyan-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Verwendete Suchkriterien
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($analysis['contract_recognition']['search_criteria'] as $criteria)
                                <span class="inline-block bg-cyan-200 text-cyan-800 px-2 py-1 rounded text-sm">{{ $criteria }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Gefundene Verträge -->
                <div class="space-y-4">
                    @foreach($analysis['contract_recognition']['found_contracts'] as $index => $contractMatch)
                        <div class="bg-white rounded-lg p-6 border border-cyan-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center mr-4">
                                        <span class="text-white font-bold text-lg">{{ $index + 1 }}</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">
                                            @if($contractMatch['contract']->supplier)
                                                {{ $contractMatch['contract']->supplier->display_name }}
                                                @if($contractMatch['contract']->supplier->supplier_number)
                                                    <span class="text-sm font-medium text-gray-600 ml-2">({{ $contractMatch['contract']->supplier->supplier_number }})</span>
                                                @endif
                                            @else
                                                Vertrag #{{ $contractMatch['contract']->id }}
                                            @endif
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            Vertrag vom {{ $contractMatch['contract']->created_at->format('d.m.Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($contractMatch['confidence_level'] === 'Sehr hoch') bg-green-100 text-green-800
                                        @elseif($contractMatch['confidence_level'] === 'Hoch') bg-blue-100 text-blue-800
                                        @elseif($contractMatch['confidence_level'] === 'Mittel') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $contractMatch['confidence_level'] }} ({{ $contractMatch['match_score'] }} Punkte)
                                    </div>
                                </div>
                            </div>

                            <!-- Vertragsdaten -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                @if($contractMatch['contract']->supplier)
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200 relative">
                                        <div class="text-sm font-medium text-gray-600">Lieferant</div>
                                        <div class="text-base font-bold text-gray-900">
                                            {{ $contractMatch['contract']->supplier->display_name }}
                                            @if($contractMatch['contract']->supplier->supplier_number)
                                                <div class="text-sm font-medium text-gray-600 mt-1">({{ $contractMatch['contract']->supplier->supplier_number }})</div>
                                            @endif
                                        </div>
                                        <a href="/admin/suppliers/{{ $contractMatch['contract']->supplier->id }}"
                                           target="_blank"
                                           class="absolute top-2 right-2 text-blue-600 hover:text-blue-800 transition-colors"
                                           title="Lieferant öffnen">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                @endif

                                @if($contractMatch['contract']->contract_number)
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200 relative">
                                        <div class="text-sm font-medium text-gray-600">Vertragsnummer</div>
                                        <div class="text-base font-bold text-gray-900 font-mono">{{ $contractMatch['contract']->contract_number }}</div>
                                        <a href="/admin/supplier-contracts/{{ $contractMatch['contract']->id }}"
                                           target="_blank"
                                           class="absolute top-2 right-2 text-blue-600 hover:text-blue-800 transition-colors"
                                           title="Vertrag öffnen">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                @endif

                                @if($contractMatch['contract']->start_date)
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <div class="text-sm font-medium text-gray-600">Vertragsbeginn</div>
                                        <div class="text-base font-bold text-gray-900">{{ $contractMatch['contract']->start_date->format('d.m.Y') }}</div>
                                    </div>
                                @endif

                                @if($contractMatch['contract']->end_date)
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <div class="text-sm font-medium text-gray-600">Vertragsende</div>
                                        <div class="text-base font-bold text-gray-900">{{ $contractMatch['contract']->end_date->format('d.m.Y') }}</div>
                                    </div>
                                @endif

                                @if($contractMatch['contract']->monthly_amount)
                                    <div class="bg-green-50 p-3 rounded border border-green-200">
                                        <div class="text-sm font-medium text-gray-600">Monatsbetrag</div>
                                        <div class="text-lg font-bold text-green-700">{{ number_format($contractMatch['contract']->monthly_amount, 2, ',', '.') }} €</div>
                                    </div>
                                @endif

                                @if($contractMatch['contract']->status)
                                    <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                        <div class="text-sm font-medium text-gray-600">Status</div>
                                        <div class="text-base font-bold text-blue-700">{{ $contractMatch['contract']->status }}</div>
                                    </div>
                                @endif
                            </div>

                            <!-- Erkennungsfelder -->
                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Gefundene Übereinstimmungen
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($contractMatch['match_details'] as $field => $value)
                                        <div class="bg-cyan-50 p-3 rounded border border-cyan-200">
                                            <div class="text-xs font-medium text-cyan-600 uppercase tracking-wide">
                                                @if($field === 'creditor_number')
                                                    Kreditorennummer
                                                @elseif($field === 'external_contract_number')
                                                    Externe Vertragsnummer
                                                @elseif($field === 'contract_recognition_1')
                                                    Vertragserkennung 1
                                                @elseif($field === 'contract_recognition_2')
                                                    Vertragserkennung 2
                                                @elseif($field === 'contract_recognition_3')
                                                    Vertragserkennung 3
                                                @elseif($field === 'supplier_name')
                                                    Lieferantenname
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $field)) }}
                                                @endif
                                            </div>
                                            <div class="text-sm font-bold text-cyan-800 font-mono break-all">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Zusätzliche Vertragsdetails -->
                            @if($contractMatch['contract']->description || $contractMatch['contract']->notes)
                                <div class="border-t border-gray-200 pt-4 mt-4">
                                    @if($contractMatch['contract']->description)
                                        <div class="mb-2">
                                            <span class="text-sm font-medium text-gray-600">Beschreibung:</span>
                                            <div class="text-sm text-gray-800 mt-1">{{ $contractMatch['contract']->description }}</div>
                                        </div>
                                    @endif
                                    @if($contractMatch['contract']->notes)
                                        <div class="mb-2">
                                            <span class="text-sm font-medium text-gray-600">Notizen:</span>
                                            <div class="text-sm text-gray-800 mt-1">{{ $contractMatch['contract']->notes }}</div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Zusammenfassung -->
                <div class="mt-6 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
                    <div class="flex items-center text-sm text-cyan-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">Automatische Vertragserkennung abgeschlossen</span>
                        <span class="ml-2">
                            • {{ $analysis['contract_recognition']['total_matches'] }} Vertrag/Verträge gefunden
                            • Suchkriterien: {{ implode(', ', $analysis['contract_recognition']['search_criteria']) }}
                        </span>
                    </div>
                </div>
            </div>
        @elseif(isset($analysis['contract_recognition']) && !$analysis['contract_recognition']['has_matching_contracts'])
            <div class="bg-gradient-to-r from-gray-50 to-slate-50 border-l-4 border-gray-400 rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gray-400 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-xl font-semibold text-gray-800">🔍 Vertragserkennung</h2>
                        <p class="text-gray-600 mt-1">
                            @if(isset($analysis['contract_recognition']['message']))
                                {{ $analysis['contract_recognition']['message'] }}
                            @else
                                Keine passenden Verträge in der Datenbank gefunden.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Hervorgehobene Absender-Information -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-xl font-semibold text-gray-800">PDF-Datei erhalten von</h2>
                    <p class="text-2xl font-bold text-blue-700 mt-1">{{ $email->from_string }}</p>
                    <div class="mt-2 text-sm text-gray-600">
                        <p><strong>E-Mail-Betreff:</strong> {{ $email->subject }}</p>
                        <p><strong>Empfangen am:</strong> {{ $email->gmail_date->format('d.m.Y H:i') }} Uhr</p>
                        @if($email->to_string)
                        <p><strong>Gesendet an:</strong> {{ $email->to_string }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Datei-Informationen -->
        <div class="bg-gradient-to-r from-gray-50 to-slate-50 border-l-4 border-gray-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-6">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gray-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">Datei-Informationen</h2>
                    <p class="text-gray-600 mt-1">Technische Details der analysierten PDF-Datei</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Dateiname</div>
                            <div class="text-lg font-bold text-gray-900 break-all">{{ $analysis['filename'] }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7c0-2.21-1.79-4-4-4H8c-2.21 0-4 1.79-4 4z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Dateigröße</div>
                            <div class="text-lg font-bold text-green-700">{{ $analysis['file_info']['size_formatted'] }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">MIME-Typ</div>
                            <div class="text-lg font-bold text-purple-700">{{ $analysis['file_info']['mime_type'] }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Seitenanzahl</div>
                            <div class="text-lg font-bold text-orange-700">{{ $analysis['basic_info']['page_count'] }} Seiten</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF-Ersteller hervorgehoben -->
        @if($analysis['basic_info']['author'] !== 'Nicht verfügbar' || $analysis['basic_info']['creator'] !== 'Nicht verfügbar')
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-xl font-semibold text-gray-800">PDF-Ersteller-Information</h2>
                    @if($analysis['basic_info']['author'] !== 'Nicht verfügbar')
                    <p class="text-2xl font-bold text-green-700 mt-1">{{ $analysis['basic_info']['author'] }}</p>
                    <p class="text-sm text-gray-600">PDF-Autor</p>
                    @endif
                    
                    @if($analysis['basic_info']['creator'] !== 'Nicht verfügbar' && $analysis['basic_info']['creator'] !== $analysis['basic_info']['author'])
                    <p class="text-lg font-semibold text-green-600 mt-2">{{ $analysis['basic_info']['creator'] }}</p>
                    <p class="text-sm text-gray-600">Erstellt mit</p>
                    @endif
                    
                    @if($analysis['basic_info']['creation_date'] !== 'Nicht verfügbar')
                    <p class="text-sm text-gray-600 mt-2"><strong>Erstellt am:</strong> {{ $analysis['basic_info']['creation_date'] }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- PDF-Metadaten -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border-l-4 border-indigo-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-6">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">PDF-Metadaten</h2>
                    <p class="text-gray-600 mt-1">Dokumenteigenschaften und Erstellungsinformationen</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Titel -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['title'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-blue-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Titel</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['title'] !== 'Nicht verfügbar' ? 'text-blue-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['title'] }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Autor -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['author'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-green-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Autor</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['author'] !== 'Nicht verfügbar' ? 'text-green-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['author'] }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Betreff -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['subject'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-purple-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Betreff</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['subject'] !== 'Nicht verfügbar' ? 'text-purple-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['subject'] }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ersteller -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['creator'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-orange-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Ersteller</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['creator'] !== 'Nicht verfügbar' ? 'text-orange-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['creator'] }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Producer -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['producer'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-teal-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Producer</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['producer'] !== 'Nicht verfügbar' ? 'text-teal-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['producer'] }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Erstellungsdatum -->
                <div class="bg-white p-4 rounded-lg border border-indigo-200 shadow-sm {{ $analysis['basic_info']['creation_date'] !== 'Nicht verfügbar' ? 'border-l-4 border-l-red-500' : '' }}">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600">Erstellungsdatum</div>
                            <div class="text-base font-semibold text-gray-900 mt-1 break-words {{ $analysis['basic_info']['creation_date'] !== 'Nicht verfügbar' ? 'text-red-700' : 'text-gray-500' }}">
                                {{ $analysis['basic_info']['creation_date'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banking-Extraktion Funktionalitäten -->
        <div class="bg-gradient-to-r from-slate-50 to-gray-50 border-l-4 border-slate-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-6">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-slate-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">Banking-Daten-Extraktion Funktionalitäten</h2>
                    <p class="text-gray-600 mt-1">Umfassende Pattern-Matching-Algorithmen für deutsche Banking-Informationen</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- IBAN-Erkennung -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">IBAN-Erkennung</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Deutsche Formate (DE...)</li>
                        <li>• Internationale Formate</li>
                        <li>• Mit/ohne Leerzeichen</li>
                        <li>• Automatische Validierung</li>
                    </ul>
                </div>

                <!-- BIC-Extraktion -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">BIC-Extraktion</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• SWIFT-Codes</li>
                        <li>• 8-11 Zeichen Format</li>
                        <li>• Internationale Standards</li>
                        <li>• Bank-Identifikation</li>
                    </ul>
                </div>

                <!-- SEPA-Mandatsreferenz -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">SEPA-Mandatsreferenz</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Mandatsreferenz-Nummern</li>
                        <li>• Verschiedene Formate</li>
                        <li>• Automatische Erkennung</li>
                        <li>• Lastschrift-Autorisierung</li>
                    </ul>
                </div>

                <!-- Gläubiger-ID -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Gläubiger-ID</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• SEPA-Gläubiger-IDs</li>
                        <li>• Deutsche CI-Nummern</li>
                        <li>• Internationale Formate</li>
                        <li>• Eindeutige Identifikation</li>
                    </ul>
                </div>

                <!-- Gesamtbetrag -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-green-600 font-bold text-sm">€</span>
                        </div>
                        <h3 class="font-semibold text-gray-800">Gesamtbetrag</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Euro-Beträge (€, EUR)</li>
                        <li>• Verschiedene Formate</li>
                        <li>• Komma/Punkt-Trennung</li>
                        <li>• Automatische Normalisierung</li>
                    </ul>
                </div>

                <!-- Abbuchungsdatum -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Abbuchungsdatum</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Deutsche Datumsformate</li>
                        <li>• DD.MM.YYYY Format</li>
                        <li>• Fälligkeitstermine</li>
                        <li>• Kontextuelle Erkennung</li>
                    </ul>
                </div>

                <!-- Bankname-Ermittlung -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Bankname-Ermittlung</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Deutsche Bankleitzahlen</li>
                        <li>• IBAN-zu-Bank Mapping</li>
                        <li>• Automatische Zuordnung</li>
                        <li>• Vollständige Bank-Namen</li>
                    </ul>
                </div>

                <!-- Verwendungszweck -->
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Verwendungszweck</h3>
                    </div>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Referenz-Texte</li>
                        <li>• Beschreibungen</li>
                        <li>• Rechnungsnummern</li>
                        <li>• Kontextuelle Extraktion</li>
                    </ul>
                </div>
            </div>

            <!-- Technische Details -->
            <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-slate-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-slate-800 mb-2">Technische Implementierung</h4>
                        <div class="text-sm text-slate-600 space-y-1">
                            <p>• <strong>Robuste Regex-Patterns:</strong> Umfassende reguläre Ausdrücke für verschiedene Banking-Formate</p>
                            <p>• <strong>Deutsche Banking-Standards:</strong> Speziell optimiert für deutsche SEPA-Lastschriftverfahren</p>
                            <p>• <strong>Intelligente Kontexterkennung:</strong> Berücksichtigung von Schlüsselwörtern und Dokumentstruktur</p>
                            <p>• <strong>Datenvalidierung:</strong> Automatische Überprüfung und Normalisierung extrahierter Daten</p>
                            <p>• <strong>Bankleitzahlen-Mapping:</strong> Integrierte deutsche Bankleitzahlen-Datenbank für Bankname-Zuordnung</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- E-Mail-Text-Analyse -->
        @if(isset($analysis['email_text_analysis']) && $analysis['email_text_analysis']['has_email_content'])
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-6 mb-6">
                <h3 class="text-xl font-bold text-purple-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    📧 E-Mail-Text-Analyse
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Rechnungsinformationen -->
                    @if(!empty($analysis['email_text_analysis']['invoice_info']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Rechnungsinformationen
                            </h4>
                            @foreach($analysis['email_text_analysis']['invoice_info'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Kundeninformationen -->
                    @if(!empty($analysis['email_text_analysis']['customer_info']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Kundeninformationen
                            </h4>
                            @foreach($analysis['email_text_analysis']['customer_info'] as $key => $value)
                                @if($value && $key !== 'address')
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2">{{ $value }}</span>
                                    </div>
                                @elseif($key === 'address' && is_array($value))
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">Adresse:</span>
                                        <div class="text-sm text-gray-800 ml-2 mt-1">
                                            {{ $value['full_address'] ?? ($value['street'] . ' ' . $value['house_number'] . ', ' . $value['postal_code'] . ' ' . $value['city']) }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Lieferanteninformationen -->
                    @if(!empty($analysis['email_text_analysis']['supplier_info']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Lieferant/Unternehmen
                                <span class="ml-2 text-xs bg-purple-100 text-purple-600 px-2 py-1 rounded">aus E-Mail-Signatur</span>
                            </h4>
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['primary_company']))
                                <div class="mb-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                                    <span class="text-sm font-medium text-purple-700">Hauptunternehmen:</span>
                                    <div class="text-lg font-bold text-purple-800 mt-1">{{ $analysis['email_text_analysis']['supplier_info']['primary_company'] }}</div>
                                </div>
                            @endif
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['companies']) && count($analysis['email_text_analysis']['supplier_info']['companies']) > 1)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">Weitere erkannte Unternehmen:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach(array_slice($analysis['email_text_analysis']['supplier_info']['companies'], 1) as $company)
                                            <span class="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ $company }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['postal_address']))
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">📮 Postanschrift:</span>
                                    <div class="text-sm text-gray-800 ml-2 mt-1 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['postal_address']['full_address'] }}</div>
                                </div>
                            @endif
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['business_address']))
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">🏢 Geschäftsadresse:</span>
                                    <div class="text-sm text-gray-800 ml-2 mt-1 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['business_address']['full_address'] }}</div>
                                </div>
                            @endif
                            
                            <!-- Zusätzliche Signatur-Informationen -->
                            @if(isset($analysis['email_text_analysis']['supplier_info']['management']))
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">👤 Geschäftsführung:</span>
                                    <span class="text-sm text-gray-800 ml-2">{{ $analysis['email_text_analysis']['supplier_info']['management'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['commercial_register']))
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">📋 Handelsregister:</span>
                                    <span class="text-sm text-gray-800 ml-2 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['commercial_register'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($analysis['email_text_analysis']['supplier_info']['vat_id']))
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">🆔 USt-IdNr.:</span>
                                    <span class="text-sm text-gray-800 ml-2 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['vat_id'] }}</span>
                                </div>
                            @endif
                            
                            <!-- Kontaktdaten aus Signatur -->
                            <div class="mt-3 pt-3 border-t border-purple-200">
                                <h5 class="text-sm font-semibold text-purple-700 mb-2">📞 Kontaktdaten aus Signatur</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @if(isset($analysis['email_text_analysis']['supplier_info']['phone']))
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 w-16">Telefon:</span>
                                            <span class="text-sm text-gray-800 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['phone'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($analysis['email_text_analysis']['supplier_info']['email']))
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 w-16">E-Mail:</span>
                                            <span class="text-sm text-blue-600 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['email'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($analysis['email_text_analysis']['supplier_info']['website']))
                                        <div class="flex items-center md:col-span-2">
                                            <span class="text-xs text-gray-500 w-16">Website:</span>
                                            <span class="text-sm text-blue-600 font-mono">{{ $analysis['email_text_analysis']['supplier_info']['website'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Zahlungsinformationen -->
                    @if(!empty($analysis['email_text_analysis']['payment_info']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Zahlungsinformationen
                            </h4>
                            @foreach($analysis['email_text_analysis']['payment_info'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2 font-mono">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Kontaktinformationen -->
                    @if(!empty($analysis['email_text_analysis']['contact_info']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                Kontaktinformationen
                            </h4>
                            @foreach($analysis['email_text_analysis']['contact_info'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Beträge -->
                    @if(!empty($analysis['email_text_analysis']['amounts']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Beträge
                            </h4>
                            @foreach($analysis['email_text_analysis']['amounts'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">
                                            @if($key === 'total')
                                                Gesamtbetrag
                                            @elseif($key === 'net')
                                                Netto
                                            @elseif($key === 'gross')
                                                Brutto
                                            @elseif($key === 'vat_amount')
                                                MwSt.-Betrag
                                            @elseif($key === 'vat_rate')
                                                MwSt.-Satz
                                            @elseif($key === 'service_amount')
                                                Service-Betrag
                                            @else
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            @endif
                                        :</span>
                                        <span class="text-sm text-gray-800 ml-2 font-semibold {{ $key === 'total' ? 'text-green-700 text-base' : '' }}">
                                            {{ $value }}{{ $key !== 'vat_rate' ? ' €' : '' }}
                                        </span>
                                        @if($key === 'total')
                                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs ml-2">Brutto</span>
                                        @elseif($key === 'service_amount')
                                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs ml-2">Netto</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Datumsangaben -->
                    @if(!empty($analysis['email_text_analysis']['dates']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Datumsangaben
                            </h4>
                            @foreach($analysis['email_text_analysis']['dates'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Referenznummern -->
                    @if(!empty($analysis['email_text_analysis']['references']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                                Referenznummern
                            </h4>
                            @foreach($analysis['email_text_analysis']['references'] as $key => $value)
                                @if($value)
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-sm text-gray-800 ml-2 font-mono">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Dienstleistungen -->
                    @if(!empty($analysis['email_text_analysis']['services']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-purple-100">
                            <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Dienstleistungen
                            </h4>
                            @foreach($analysis['email_text_analysis']['services'] as $key => $value)
                                @if($value)
                                    @if(is_array($value))
                                        <div class="mb-2">
                                            <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            <div class="text-sm text-gray-800 ml-2 mt-1">
                                                @foreach($value as $item)
                                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $item }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            <span class="text-sm text-gray-800 ml-2">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Gefundene E-Mail-Indikatoren -->
                @if(isset($analysis['email_text_analysis']['found_indicators']) && !empty($analysis['email_text_analysis']['found_indicators']))
                    <div class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-200">
                        <h4 class="font-semibold text-purple-700 mb-2">Erkannte E-Mail-Indikatoren:</h4>
                        <div class="flex flex-wrap gap-1">
                            @foreach($analysis['email_text_analysis']['found_indicators'] as $indicator)
                                <span class="inline-block bg-purple-200 text-purple-800 px-2 py-1 rounded text-xs">{{ $indicator }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- E-Mail-Weiterleitungs-Analyse -->
        @if(isset($analysis['forwarding_analysis']) && $analysis['forwarding_analysis']['is_forwarded'])
            <div class="bg-gradient-to-r from-orange-50 to-red-50 border-l-4 border-orange-500 rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-2xl font-bold text-gray-800">📧 E-Mail-Weiterleitungs-Analyse</h2>
                        <p class="text-gray-600 mt-1">Diese E-Mail wurde als Weiterleitung erkannt</p>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $analysis['forwarding_analysis']['confidence_score'] }}% Sicherheit
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Ursprünglicher Absender -->
                    @if($analysis['forwarding_analysis']['original_sender']['name'] || $analysis['forwarding_analysis']['original_sender']['email'])
                        <div class="bg-white p-4 rounded-lg border-l-4 border-l-blue-500 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-800">Ursprünglicher Absender</h3>
                            </div>
                            @if($analysis['forwarding_analysis']['original_sender']['name'])
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">Name:</span>
                                    <div class="text-lg font-bold text-blue-700">{{ $analysis['forwarding_analysis']['original_sender']['name'] }}</div>
                                </div>
                            @endif
                            @if($analysis['forwarding_analysis']['original_sender']['email'])
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">E-Mail:</span>
                                    <div class="text-sm text-blue-600 font-mono break-all">{{ $analysis['forwarding_analysis']['original_sender']['email'] }}</div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Ursprüngliche Nachricht -->
                    @if($analysis['forwarding_analysis']['original_message']['date'] || $analysis['forwarding_analysis']['original_message']['subject'])
                        <div class="bg-white p-4 rounded-lg border-l-4 border-l-green-500 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-800">Ursprüngliche Nachricht</h3>
                            </div>
                            @if($analysis['forwarding_analysis']['original_message']['date'])
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">Datum:</span>
                                    <div class="text-sm text-green-700 font-medium">{{ $analysis['forwarding_analysis']['original_message']['date'] }}</div>
                                </div>
                            @endif
                            @if($analysis['forwarding_analysis']['original_message']['subject'])
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-600">Betreff:</span>
                                    <div class="text-sm text-green-700 break-words">{{ $analysis['forwarding_analysis']['original_message']['subject'] }}</div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Weiterleitungs-Details -->
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-purple-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-800">Weiterleitungs-Details</h3>
                        </div>
                        @if($analysis['forwarding_analysis']['forwarding_chain_count'] > 1)
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-600">Weiterleitungs-Kette:</span>
                                <div class="text-lg font-bold text-purple-700">{{ $analysis['forwarding_analysis']['forwarding_chain_count'] }} Weiterleitungen</div>
                            </div>
                        @endif
                        <div class="mb-2">
                            <span class="text-sm font-medium text-gray-600">Erkennungs-Methode:</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if($analysis['forwarding_analysis']['header_indicators'])
                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">Header-Analyse</span>
                                @endif
                                @if($analysis['forwarding_analysis']['text_indicators'])
                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">Text-Pattern</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Erkennungs-Details -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Header-Indikatoren -->
                    @if(!empty($analysis['forwarding_analysis']['header_indicators']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-orange-100">
                            <h4 class="font-semibold text-orange-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Header-Indikatoren
                            </h4>
                            <div class="space-y-2">
                                @foreach($analysis['forwarding_analysis']['header_indicators'] as $indicator)
                                    <div class="flex items-center text-sm">
                                        <div class="w-2 h-2 bg-orange-500 rounded-full mr-2"></div>
                                        <span class="text-gray-700">{{ $indicator }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Text-Pattern -->
                    @if(!empty($analysis['forwarding_analysis']['text_indicators']))
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-orange-100">
                            <h4 class="font-semibold text-orange-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                </svg>
                                Gefundene Text-Pattern
                            </h4>
                            <div class="space-y-2">
                                @foreach($analysis['forwarding_analysis']['text_indicators'] as $pattern)
                                    <div class="bg-orange-50 p-2 rounded border border-orange-200">
                                        <div class="text-xs font-medium text-orange-700 mb-1">Pattern:</div>
                                        <div class="text-sm text-gray-800 font-mono break-all">{{ $pattern['pattern'] ?? $pattern }}</div>
                                        @if(isset($pattern['extracted_info']))
                                            <div class="text-xs text-orange-600 mt-1">
                                                Extrahierte Info: {{ $pattern['extracted_info'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Ursprünglicher Nachrichten-Block -->
                @if($analysis['forwarding_analysis']['original_message']['content'])
                    <div class="mt-6 bg-white rounded-lg p-4 shadow-sm border border-orange-100">
                        <div class="flex items-center justify-between cursor-pointer" onclick="toggleSection('original-message-content')">
                            <h4 class="font-semibold text-orange-700 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                Ursprünglicher Nachrichten-Inhalt
                            </h4>
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-2">Details anzeigen</span>
                                <svg id="original-message-content-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <div id="original-message-content" class="collapsible-content mt-4">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 max-h-96 overflow-y-auto">
                                <pre class="text-sm font-mono text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $analysis['forwarding_analysis']['original_message']['content'] }}</pre>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Zusammenfassung -->
                <div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-center text-sm text-orange-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">Weiterleitungs-Analyse abgeschlossen</span>
                        <span class="ml-2">
                            • Confidence-Score: {{ $analysis['forwarding_analysis']['confidence_score'] }}%
                            @if($analysis['forwarding_analysis']['original_sender']['email'])
                            • Ursprünglicher Absender: {{ $analysis['forwarding_analysis']['original_sender']['email'] }}
                            @endif
                            @if($analysis['forwarding_analysis']['forwarding_chain_count'] > 1)
                            • {{ $analysis['forwarding_analysis']['forwarding_chain_count'] }} Weiterleitungen in der Kette
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @endif


        <!-- Strukturierte Daten -->
        @if(!empty($analysis['structured_data']) && (
            !empty($analysis['structured_data']['emails']) || 
            !empty($analysis['structured_data']['phone_numbers']) || 
            !empty($analysis['structured_data']['dates']) || 
            !empty($analysis['structured_data']['urls']) || 
            !empty($analysis['structured_data']['numbers'])
        ))
        <div class="analysis-section">
            <h2 class="analysis-header">Extrahierte strukturierte Daten</h2>
            
            @if(!empty($analysis['structured_data']['emails']))
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                    </span>
                    Gefundene E-Mail-Adressen
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($analysis['structured_data']['emails'] as $email)
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-xs">@</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-blue-800 break-all">{{ $email }}</div>
                                <div class="text-xs text-blue-600 mt-1">
                                    @if(strpos($email, '@gmail.com') !== false)
                                    Gmail-Adresse
                                    @elseif(strpos($email, '@outlook.com') !== false || strpos($email, '@hotmail.com') !== false)
                                    Outlook-Adresse
                                    @elseif(preg_match('/\.(de|com|org|net)$/', $email))
                                    {{ strtoupper(substr(strrchr($email, '.'), 1)) }}-Domain
                                    @else
                                    E-Mail-Adresse
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-blue-700 bg-blue-50 p-2 rounded">
                    <strong>{{ count($analysis['structured_data']['emails']) }}</strong> E-Mail-Adresse(n) automatisch erkannt
                </div>
            </div>
            @endif

            @if(!empty($analysis['structured_data']['phone_numbers']))
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </span>
                    Gefundene Telefonnummern
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($analysis['structured_data']['phone_numbers'] as $phone)
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-green-800">{{ $phone }}</div>
                                <div class="text-xs text-green-600 mt-1">
                                    @if(preg_match('/^\+49/', $phone))
                                    Deutsche Nummer
                                    @elseif(preg_match('/^0[1-9]/', $phone))
                                    Nationale Nummer
                                    @elseif(preg_match('/^\+/', $phone))
                                    Internationale Nummer
                                    @else
                                    Telefonnummer
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-green-700 bg-green-50 p-2 rounded">
                    <strong>{{ count($analysis['structured_data']['phone_numbers']) }}</strong> Telefonnummer(n) automatisch erkannt
                </div>
            </div>
            @endif

            @if(!empty($analysis['structured_data']['dates']))
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    Gefundene Datumsangaben
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach($analysis['structured_data']['dates'] as $date)
                    <div class="bg-gradient-to-r from-purple-50 to-violet-50 border border-purple-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-purple-800">{{ $date }}</div>
                                <div class="text-xs text-purple-600 mt-1">
                                    @if(preg_match('/\d{1,2}\.\d{1,2}\.\d{4}/', $date))
                                    Deutsches Format
                                    @elseif(preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $date))
                                    ISO-Format
                                    @elseif(preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $date))
                                    US-Format
                                    @else
                                    Datumsangabe
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-purple-700 bg-purple-50 p-2 rounded">
                    <strong>{{ count($analysis['structured_data']['dates']) }}</strong> Datumsangabe(n) automatisch erkannt
                </div>
            </div>
            @endif

            @if(!empty($analysis['structured_data']['urls']))
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                    </span>
                    🔗 Gefundene URLs
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @foreach($analysis['structured_data']['urls'] as $url)
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-indigo-800 break-all">{{ $url }}</div>
                                <div class="text-xs text-indigo-600 mt-1">
                                    @if(strpos($url, 'https://') === 0)
                                    Sichere Verbindung (HTTPS)
                                    @elseif(strpos($url, 'http://') === 0)
                                    Ungesicherte Verbindung (HTTP)
                                    @elseif(strpos($url, 'www.') === 0)
                                    Website-Adresse
                                    @elseif(filter_var($url, FILTER_VALIDATE_EMAIL))
                                    E-Mail-Link
                                    @else
                                    Web-Adresse
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-indigo-700 bg-indigo-50 p-2 rounded">
                    <strong>{{ count($analysis['structured_data']['urls']) }}</strong> URL(s) automatisch erkannt
                </div>
            </div>
            @endif

            @if(!empty($analysis['structured_data']['numbers']))
            <div class="mb-4">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </span>
                    Gefundene Geldbeträge
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($analysis['structured_data']['numbers'] as $number)
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mr-2">
                                    <span class="text-white text-xs font-bold">€</span>
                                </div>
                                <span class="text-lg font-bold text-green-800">{{ $number }}</span>
                            </div>
                            @if(preg_match('/(\d+[.,]\d{2})/', $number))
                            <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded">
                                Betrag
                            </div>
                            @elseif(preg_match('/(\d+[.,]\d{1})/', $number))
                            <div class="text-xs text-yellow-600 bg-yellow-100 px-2 py-1 rounded">
                                Preis
                            </div>
                            @else
                            <div class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                Zahl
                            </div>
                            @endif
                        </div>
                        @if(strlen($number) > 10)
                        <div class="text-xs text-gray-500 mt-1 break-all">
                            {{ $number }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                @if(count($analysis['structured_data']['numbers']) > 0)
                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center text-sm text-green-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">{{ count($analysis['structured_data']['numbers']) }} Geldbeträge/Zahlen gefunden</span>
                        <span class="ml-2 text-green-600">
                            • Automatisch erkannte Währungsangaben und numerische Werte
                        </span>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        <!-- ZuGFeRD-Analyse -->
        @if(!empty($analysis['zugferd_data']) && $analysis['zugferd_data']['is_zugferd'])
        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">🧾 ZuGFeRD-Rechnung erkannt!</h2>
                    <p class="text-gray-600 mt-1">Diese PDF enthält strukturierte Rechnungsdaten nach dem ZuGFeRD-Standard</p>
                </div>
            </div>

            <!-- ZuGFeRD-Grundinformationen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @if($analysis['zugferd_data']['version'])
                <div class="bg-white p-4 rounded-lg border border-yellow-200">
                    <div class="text-sm font-medium text-gray-600">Version</div>
                    <div class="text-lg font-bold text-yellow-700">{{ $analysis['zugferd_data']['version'] }}</div>
                </div>
                @endif
                
                @if($analysis['zugferd_data']['profile'])
                <div class="bg-white p-4 rounded-lg border border-yellow-200">
                    <div class="text-sm font-medium text-gray-600">Profil</div>
                    <div class="text-lg font-bold text-yellow-700">{{ $analysis['zugferd_data']['profile'] }}</div>
                </div>
                @endif
                
                <div class="bg-white p-4 rounded-lg border border-yellow-200">
                    <div class="text-sm font-medium text-gray-600">Erkannte Indikatoren</div>
                    <div class="text-sm text-yellow-700">{{ count($analysis['zugferd_data']['found_indicators']) }} gefunden</div>
                </div>
            </div>

            <!-- Rechnungsdaten -->
            @if(!empty($analysis['zugferd_data']['invoice_data']))
            <div class="bg-white rounded-lg p-6 border border-yellow-200 mb-4">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">📋 Rechnungsinformationen</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @if(isset($analysis['zugferd_data']['invoice_data']['invoice_number']))
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <div class="text-sm font-medium text-gray-600">Rechnungsnummer</div>
                        <div class="text-lg font-bold text-yellow-800">{{ $analysis['zugferd_data']['invoice_data']['invoice_number'] }}</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['invoice_date']))
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <div class="text-sm font-medium text-gray-600">Rechnungsdatum</div>
                        <div class="text-lg font-bold text-yellow-800">{{ $analysis['zugferd_data']['invoice_data']['invoice_date'] }}</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['due_date']))
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <div class="text-sm font-medium text-gray-600">Fälligkeitsdatum</div>
                        <div class="text-lg font-bold text-yellow-800">{{ $analysis['zugferd_data']['invoice_data']['due_date'] }}</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['total_amount']))
                    <div class="bg-green-50 p-3 rounded border border-green-200">
                        <div class="text-sm font-medium text-gray-600">Gesamtbetrag</div>
                        <div class="text-xl font-bold text-green-800">{{ $analysis['zugferd_data']['invoice_data']['total_amount'] }} €</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['net_amount']))
                    <div class="bg-blue-50 p-3 rounded border border-blue-200">
                        <div class="text-sm font-medium text-gray-600">Nettobetrag</div>
                        <div class="text-lg font-bold text-blue-800">{{ $analysis['zugferd_data']['invoice_data']['net_amount'] }} €</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['vat_amount']))
                    <div class="bg-purple-50 p-3 rounded border border-purple-200">
                        <div class="text-sm font-medium text-gray-600">MwSt.-Betrag</div>
                        <div class="text-lg font-bold text-purple-800">{{ $analysis['zugferd_data']['invoice_data']['vat_amount'] }} €</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['vat_rate']))
                    <div class="bg-purple-50 p-3 rounded border border-purple-200">
                        <div class="text-sm font-medium text-gray-600">MwSt.-Satz</div>
                        <div class="text-lg font-bold text-purple-800">{{ $analysis['zugferd_data']['invoice_data']['vat_rate'] }}</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['seller']))
                    <div class="bg-gray-50 p-3 rounded border border-gray-200 md:col-span-2">
                        <div class="text-sm font-medium text-gray-600">Verkäufer/Lieferant</div>
                        <div class="text-lg font-bold text-gray-800">{{ $analysis['zugferd_data']['invoice_data']['seller'] }}</div>
                    </div>
                    @endif
                    
                    @if(isset($analysis['zugferd_data']['invoice_data']['buyer']))
                    <div class="bg-gray-50 p-3 rounded border border-gray-200 md:col-span-2">
                        <div class="text-sm font-medium text-gray-600">Käufer</div>
                        <div class="text-lg font-bold text-gray-800">{{ $analysis['zugferd_data']['invoice_data']['buyer'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
    
            <!-- Banking-Informationen -->
            @if(!empty($analysis['banking_data']) && $analysis['banking_data']['has_banking_info'])
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-l-4 border-emerald-500 rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-2xl font-bold text-gray-800">🏦 Banking-Informationen erkannt!</h2>
                        <p class="text-gray-600 mt-1">SEPA-Lastschrift und Bankdaten wurden automatisch extrahiert</p>
                    </div>
                </div>
    
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Gesamtbetrag -->
                    @if($analysis['banking_data']['total_amount'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-green-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-green-600 font-bold text-sm">€</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">Gesamtbetrag</div>
                                <div class="text-xl font-bold text-green-700">{{ $analysis['banking_data']['total_amount'] }} €</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- Abbuchungsdatum -->
                    @if($analysis['banking_data']['debit_date'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-blue-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">Abbuchungsdatum</div>
                                <div class="text-lg font-bold text-blue-700">{{ $analysis['banking_data']['debit_date'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- SEPA-Mandat -->
                    @if($analysis['banking_data']['sepa_mandate'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-purple-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">SEPA-Mandat</div>
                                <div class="text-lg font-bold text-purple-700 break-all">{{ $analysis['banking_data']['sepa_mandate'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- IBAN -->
                    @if($analysis['banking_data']['iban'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-indigo-500 shadow-sm md:col-span-2">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">IBAN</div>
                                <div class="text-lg font-bold text-indigo-700 font-mono break-all">{{ $analysis['banking_data']['iban'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- Bankname -->
                    @if($analysis['banking_data']['bank_name'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-teal-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">Bank</div>
                                <div class="text-lg font-bold text-teal-700">{{ $analysis['banking_data']['bank_name'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- BIC -->
                    @if($analysis['banking_data']['bic'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-orange-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">BIC</div>
                                <div class="text-lg font-bold text-orange-700 font-mono">{{ $analysis['banking_data']['bic'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- Gläubiger-ID -->
                    @if($analysis['banking_data']['creditor_id'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-red-500 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">Gläubiger-ID</div>
                                <div class="text-lg font-bold text-red-700 font-mono break-all">{{ $analysis['banking_data']['creditor_id'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
    
                    <!-- Verwendungszweck -->
                    @if($analysis['banking_data']['reference'])
                    <div class="bg-white p-4 rounded-lg border-l-4 border-l-gray-500 shadow-sm md:col-span-2 lg:col-span-3">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-600">Verwendungszweck / Referenz</div>
                                <div class="text-base font-semibold text-gray-700 break-words">{{ $analysis['banking_data']['reference'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
    
                <!-- Zusammenfassung -->
                <div class="mt-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <div class="flex items-center text-sm text-emerald-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">Banking-Informationen automatisch erkannt</span>
                        <span class="ml-2">
                            • SEPA-Lastschriftverfahren
                            @if($analysis['banking_data']['total_amount'])
                            • Betrag: {{ $analysis['banking_data']['total_amount'] }} €
                            @endif
                            @if($analysis['banking_data']['debit_date'])
                            • Fällig: {{ $analysis['banking_data']['debit_date'] }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            @endif
    
            <!-- XML-Daten -->
            @if(!empty($analysis['zugferd_data']['xml_content']))
            <div class="bg-white rounded-lg p-6 border border-yellow-200 mb-4">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">ZuGFeRD XML-Daten</h3>
                
                @if(isset($analysis['zugferd_data']['xml_content']['raw_xml']))
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">XML-Deklaration gefunden</h4>
                    <div class="bg-gray-50 p-3 rounded font-mono text-sm border">
                        {{ Str::limit($analysis['zugferd_data']['xml_content']['raw_xml'], 200) }}
                    </div>
                </div>
                @endif
                
                @if(isset($analysis['zugferd_data']['xml_content']['cross_industry_invoice']))
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">CrossIndustryInvoice Element</h4>
                    <div class="bg-green-50 p-3 rounded text-sm border border-green-200">
                        <span class="text-green-700 font-medium">✓ ZuGFeRD Hauptelement gefunden</span>
                    </div>
                </div>
                @endif
                
                @if(isset($analysis['zugferd_data']['xml_content']['exchanged_document']))
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">ExchangedDocument Element</h4>
                    <div class="bg-blue-50 p-3 rounded text-sm border border-blue-200">
                        <span class="text-blue-700 font-medium">✓ Dokumentaustausch-Element gefunden</span>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Anhänge -->
            @if(!empty($analysis['zugferd_data']['attachments']))
            <div class="bg-white rounded-lg p-6 border border-yellow-200 mb-4">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">📎 Eingebettete Dateien</h3>
                <div class="space-y-2">
                    @foreach($analysis['zugferd_data']['attachments'] as $key => $attachment)
                    <div class="bg-gray-50 p-3 rounded border">
                        <div class="font-medium text-gray-700">{{ $key }}</div>
                        <div class="text-sm text-gray-600">{{ is_array($attachment) ? json_encode($attachment) : $attachment }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Validierungsfehler -->
            @if(!empty($analysis['zugferd_data']['validation_errors']))
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h3 class="text-lg font-semibold text-red-800 mb-2">⚠️ Validierungshinweise</h3>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($analysis['zugferd_data']['validation_errors'] as $error)
                    <li class="text-red-700 text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Erkannte Indikatoren -->
            @if(!empty($analysis['zugferd_data']['found_indicators']))
            <div class="bg-white rounded-lg p-4 border border-yellow-200 mt-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">🔍 Erkannte ZuGFeRD-Indikatoren</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($analysis['zugferd_data']['found_indicators'] as $indicator)
                    <span class="bg-yellow-100 border border-yellow-300 px-2 py-1 rounded text-sm font-mono">{{ $indicator }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- XML-Daten -->
        @if(!empty($analysis['xml_data']) && $analysis['xml_data']['contains_xml'])
        <div class="analysis-section">
            <h2 class="analysis-header">🏷️ XML-Daten</h2>
            
            @if(isset($analysis['xml_data']['xml_declaration']))
            <div class="mb-4">
                <h3 class="font-semibold text-gray-700 mb-2">XML-Deklaration</h3>
                <div class="bg-gray-50 p-3 rounded font-mono text-sm">
                    {{ $analysis['xml_data']['xml_declaration'] }}
                </div>
            </div>
            @endif

            <div class="mb-4">
                <h3 class="font-semibold text-gray-700 mb-2">Gefundene XML-Tags ({{ $analysis['xml_data']['tag_count'] }} insgesamt)</h3>
                <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                    @foreach(array_slice($analysis['xml_data']['found_tags'], 0, 50) as $tag)
                    <span class="bg-green-50 border border-green-200 p-1 rounded text-xs font-mono">{{ $tag }}</span>
                    @endforeach
                    @if(count($analysis['xml_data']['found_tags']) > 50)
                    <span class="text-gray-500 text-sm">... und {{ count($analysis['xml_data']['found_tags']) - 50 }} weitere</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Seiten-Informationen -->
        @if(!empty($analysis['pages_info']))
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border border-gray-200">
            <div class="flex items-center justify-between cursor-pointer" onclick="toggleSection('pages-content')">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Seiten-Informationen</h2>
                        <p class="text-sm text-gray-600">{{ count($analysis['pages_info']) }} Seiten mit Textvorschau</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-2">Details anzeigen</span>
                    <svg id="pages-content-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div id="pages-content" class="collapsible-content mt-4">
                <div class="space-y-3">
                    @foreach($analysis['pages_info'] as $page)
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-800 flex items-center">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">
                                    {{ $page['page_number'] }}
                                </span>
                                Seite {{ $page['page_number'] }}
                            </h3>
                            <span class="text-sm text-gray-500 bg-white px-2 py-1 rounded">{{ $page['text_length'] }} Zeichen</span>
                        </div>
                        <div class="text-sm text-gray-700 bg-white p-3 rounded border border-gray-200 font-mono text-xs leading-relaxed">
                            {{ $page['text_preview'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Vollständiger Textinhalt -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border border-gray-200">
            <div class="flex items-center justify-between cursor-pointer" onclick="toggleSection('text-content')">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Vollständiger Textinhalt</h2>
                        <p class="text-sm text-gray-600">
                            @if($analysis['text_content'])
                            {{ number_format(strlen($analysis['text_content'])) }} Zeichen extrahiert
                            @else
                            Kein Text verfügbar
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-2">Text anzeigen</span>
                    <svg id="text-content-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div id="text-content" class="collapsible-content mt-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 max-h-96 overflow-y-auto">
                    @if($analysis['text_content'])
                    <pre class="text-sm font-mono text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $analysis['text_content'] }}</pre>
                    @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-medium">Kein Text extrahiert</p>
                        <p class="text-sm">Aus dieser PDF-Datei konnte kein lesbarer Text extrahiert werden.</p>
                    </div>
                    @endif
                </div>
                
                @if($analysis['text_content'])
                <div class="mt-3 flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-2 rounded">
                    <span>
                        <strong>Statistiken:</strong>
                        {{ number_format(strlen($analysis['text_content'])) }} Zeichen,
                        {{ number_format(str_word_count($analysis['text_content'])) }} Wörter,
                        {{ number_format(substr_count($analysis['text_content'], "\n") + 1) }} Zeilen
                    </span>
                    <button onclick="navigator.clipboard.writeText(document.querySelector('#text-content pre').textContent)"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">
                        Text kopieren
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Zurück-Button -->
        <div class="text-center mt-8">
            <button onclick="window.close()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Fenster schließen
            </button>
        </div>
    </div>
</body>
</html>