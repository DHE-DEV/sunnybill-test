<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solaranlagen-Abrechnung</title>
    <style>
        @page {
            margin: {{ $companySetting->pdf_margins ?? '1.5cm 1.5cm 4cm 1.5cm' }};
            size: A4;
            @bottom-center {
                content: "";
            }
        }
        
        @page :first {
            margin: {{ $companySetting->pdf_margins ?? '1.5cm 1.5cm 4cm 1.5cm' }};
            @bottom-center {
                content: "";
            }
        }
        
        @page :left, @page :right {
            margin-top: {{ $companySetting->pdf_margins ? explode(' ', $companySetting->pdf_margins)[0] : '1.5cm' }};
            margin-top: calc({{ $companySetting->pdf_margins ? explode(' ', $companySetting->pdf_margins)[0] : '1.5cm' }} + 20px);
            margin-right: {{ $companySetting->pdf_margins ? (explode(' ', $companySetting->pdf_margins)[1] ?? '1.5cm') : '1.5cm' }};
            margin-bottom: {{ $companySetting->pdf_margins ? (explode(' ', $companySetting->pdf_margins)[2] ?? '1.5cm') : '1.5cm' }};
            margin-left: {{ $companySetting->pdf_margins ? (explode(' ', $companySetting->pdf_margins)[3] ?? '1.5cm') : '1.5cm' }};
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
            counter-reset: page;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .logo {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }
        
        .logo img {
            max-width: 150px;
            max-height: 60px;
        }
        
        .company-info {
            display: table-cell;
            width: 60%;
            text-align: right;
            vertical-align: top;
            font-size: 9pt;
        }
        
        .company-info h1 {
            margin: 0 0 10px 0;
            font-size: 16pt;
            color: #2563eb;
        }
        
        .recipient {
            margin: 30px 0;
            width: 50%;
        }
        
        .recipient h3 {
            margin: 0 0 5px 0;
            font-size: 11pt;
            color: #555;
        }
        
        .billing-info {
            float: right;
            width: 45%;
            margin-top: -108px;
            font-size: 9pt;
        }
        
        .billing-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .billing-info td {
            padding: 3px 5px;
            border-bottom: 1px solid #eee;
        }
        
        .billing-info td:first-child {
            font-weight: bold;
            width: 40%;
        }
        
        .billing-info td:last-child {
            text-align: right;
        }
        
        .document-title {
            clear: both;
            margin: 40px 0 30px 0;
            text-align: center;
        }
        
        .document-title h2 {
            margin: 0;
            font-size: 18pt;
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            display: inline-block;
        }
        
        .billing-period {
            text-align: center;
            margin: 20px 0;
            font-size: 12pt;
            color: #2563eb;
            #color: #666;
            border-bottom: 2px solid #2563eb;
        }
        
        .plant-info {
            background: #f8f9fa;
            padding: 15px;
            margin: 0px 0;
            #border-left: 4px solid #2563eb;
        }
        
        .plant-info h3 {
            margin: 0 0 10px 0;
            color: #2563eb;
        }
        
        .plant-details {
            display: table;
            width: 100%;
        }
        
        .plant-details > div:first-child {
            display: table-cell;
            width: 56%;
            vertical-align: top;
        }
        
        .plant-details > div:last-child {
            display: table-cell;
            width: 44%;
            vertical-align: top;
        }
        
        .energy-details {
            display: table;
            width: 100%;
        }
        
        .energy-details > div:first-child {
            display: table-cell;
            width: 56%;
            vertical-align: top;
        }
        
        .energy-details > div:nth-child(2) {
            display: table-cell;
            width: 27%;
            vertical-align: top;
        }
        
        .energy-details > div:last-child {
            display: table-cell;
            width: 17%;
            vertical-align: top;
        }
        
        .positions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .positions-table th {
            #background: #2563eb;
            background: #96989aff;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .positions-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .positions-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .positions-table .number {
            text-align: right;
            #font-family: 'Courier New', monospace;
        }
        
        .totals {
            float: right;
            width: 200px;
            margin: 20px 0;
            margin-right: 20px;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            padding: 4px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        
        .totals .subtotal-row {
            font-weight: normal;
        }
        
        .totals .total-row {
            font-weight: bold;
            font-size: 10pt;
            background: #2563eb;
            color: white;
        }
        
        .breakdown {
            clear: both;
            margin: 15px 0;
        }
        
        .breakdown h3 {
            color: #2563eb;
            border-bottom: 1px solid #2563eb;
            padding-bottom: 5px;
        }
        
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }
        
        .breakdown-table th {
            background: #f8f9fa;
            padding: 8px;
            text-align: left;
            #border: 1px solid #ddd;
        }
        
        .breakdown-table .article-header th {
            background: #f8f9fa !important;
        }
        
        .breakdown-table td {
            padding: 6px 8px;
            #border: 1px solid #ddd;
        }
        
        .breakdown-table .number {
            text-align: right;
            #font-family: 'Courier New', monospace;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 8pt;
            color: #666;
            z-index: 9999;
        }
        
        .footer-first-page {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 8pt;
            color: #666;
            z-index: 9999;
        }
        
        .footer-content {
            display: table;
            width: 100%;
        }
        
        .footer-section {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
        }
        
        .page-number::after {
            content: counter(page);
        }
        
        .total-pages::after {
            content: counter(pages);
        }
        
        /* Alternative approach for PDF engines that don't support counter(pages) */
        @page {
            @bottom-center {
                content: counter(page) " / " counter(pages);
            }
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .mt-0 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <!-- Leer für Platz -->
        </div>
        <div class="company-info">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" 
                             alt="Firmenlogo" 
                             style="max-width: 150px; max-height: 60px; object-fit: contain;">
                    @endif
            <!--<h3>{{ $companySetting->company_name }}</h3>-->
        </div>
    </div>

    <!-- Empfänger -->
    <div class="recipient">
        <h3>Rechnungsempfänger:</h3>
        @if($customer->customer_type === 'business' && $customer->company_name)
            <strong>{{ $customer->company_name }}</strong><br>
            {{ $customer->name }}<br>
        @else
            <strong>{{ $customer->name }}</strong><br>
        @endif
        @if($customer->street)
            {{ $customer->street }}<br>
        @endif
        {{ $customer->postal_code }} {{ $customer->city }}
        @if($customer->country && $customer->country !== 'Deutschland')
            <br>{{ $customer->country }}
        @endif
    </div>

    <!-- Rechnungsinfo -->
    <div class="billing-info">
        <table>
            <tr>
                <td>Rechnungs-Nr.:</td>
                <td>{{ $billing->invoice_number }}</td>
            </tr>
            <tr>
                <td>Kundennummer:</td>
                <td>{{ $customer->customer_number }}</td>
            </tr>
            <tr>
                <td>Datum:</td>
                <td>{{ $generatedAt->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td>Periode:</td>
                <td>{{ $monthName }} {{ $billing->billing_year }}</td>
            </tr>
        </table>
    </div>

    <!-- Titel -->
    <div class="document-title">
        <!--<h2>Solaranlagen-Abrechnung</h2>-->
    </div>
    
    <div class="billing-period">
        <h3>Kundeninformation zur Abrechnungsperiode {{ $monthName }} {{ $billing->billing_year }}</h3>
    </div>

    <!-- Anlageninfo -->
    <div class="plant-info">
        <h3>Solaranlage: {{ $solarPlant->name }}</h3>
        @if($solarPlant->total_capacity_kw)
            <div style="color: #2563eb; margin: -10px 0 10px 0;">
                Technische Gesamtleistung der Anlage: {{ number_format($solarPlant->total_capacity_kw, 2, ',', '.') }} kWp
            </div>
        @endif
        <div class="plant-details">
            <div>
                <strong>Standort:</strong><br>
                @if($solarPlant->location)
                    @php
                        // Formatiere Standort als Straße<br>PLZ Ort
                        $location = trim($solarPlant->location);
                        
                        // Trenne verschiedene Adressteile
                        $parts = preg_split('/[,;|]/', $location);
                        $parts = array_map('trim', $parts);
                        $parts = array_filter($parts);
                        
                        if (count($parts) >= 2) {
                            // Erste Zeile: Straße
                            $street = $parts[0];
                            
                            // Versuche PLZ und Ort aus den restlichen Teilen zu identifizieren
                            $remaining = array_slice($parts, 1);
                            $address = implode(' ', $remaining);
                            
                            $formattedLocation = $street . '<br>' . $address;
                        } else {
                            // Fallback: versuche PLZ + Ort Pattern zu finden
                            if (preg_match('/^(.+?)[\s]+(\d{5}[\s]+.+)$/u', $location, $matches)) {
                                $formattedLocation = trim($matches[1]) . '<br>' . trim($matches[2]);
                            } else {
                                $formattedLocation = $location;
                            }
                        }
                    @endphp
                    {!! $formattedLocation !!}
                @else
                    Kein Standort hinterlegt
                    @if($solarPlant->total_capacity_kw)
                        <br><span style="color: #2563eb; font-weight: bold;">({{ number_format($solarPlant->total_capacity_kw, 2, ',', '.') }} kWp)</span>
                    @endif
                @endif
            </div>
            <div>
                <strong>Ihr Anlagenanteil:</strong><br>
                @if($currentParticipationKwp)
                    {{ number_format($currentParticipationKwp, 2, ',', '.') }} kWp 
                    ({{ number_format($currentPercentage, 2, ',', '.') }}%)
                @else
                    {{ number_format($currentPercentage, 2, ',', '.') }}%
                @endif
            </div>
        </div>
        @if($billing->produced_energy_kwh)
        <div style="margin-top: 10px; padding: 10px; background-color: #f0f8ff;">
            <div class="energy-details">
                <div>
                    <strong>Produzierte Energie im {{ $monthName }} {{ $billing->billing_year }}:</strong><br>
                    {{ number_format($billing->produced_energy_kwh, 3, ',', '.') }} kWh
                </div>
                @if($billing->produced_energy_kwh > 0 && $currentPercentage > 0)
                <div>
                    <strong>Ihr Anteil:</strong><br>
                    {{ number_format(($billing->produced_energy_kwh * $currentPercentage / 100), 3, ',', '.') }} kWh
                </div>
                @endif
                <div>
                    &nbsp;
                </div>
            </div>
        </div>
        @endif
        <div style="height: 1px;">
        </div>
    </div>

    <!-- Positionen -->
    <table class="positions-table">
        <thead>
            <tr>
                <th>Pos.</th>
                <th>Beschreibung</th>
                <th>Menge</th>
                <th>Einheit</th>
                <th class="number">Einzelpreis</th>
                <th class="number">Gesamtpreis</th>
            </tr>
        </thead>
        <tbody>
            <!-- Gutschriften/Einnahmen -->
            @if($billing->total_credits > 0)
            <tr>
                <td>1</td>
                <td>
                    <strong>Einnahmen/Gutschriften</strong><br>
                    <small>{{ $monthName }} {{ $billing->billing_year }} - {{ number_format($currentPercentage, 2, ',', '.') }}% Anteil</small>
                </td>
                <td class="number">1</td>
                <td>Monat</td>
                <td class="number">{{ number_format($billing->total_credits, 2, ',', '.') }} €</td>
                <td class="number">{{ number_format($billing->total_credits, 2, ',', '.') }} €</td>
            </tr>
            @endif

            <!-- Kosten -->
            @if($billing->total_costs > 0)
            <tr>
                <td>{{ $billing->total_credits > 0 ? 2 : 1 }}</td>
                <td>
                    <strong>Betriebskosten</strong><br>
                    <small>{{ $monthName }} {{ $billing->billing_year }} - {{ number_format($currentPercentage, 2, ',', '.') }}% Anteil</small>
                </td>
                <td class="number">1</td>
                <td>Monat</td>
                <td class="number">{{ number_format($billing->total_costs, 2, ',', '.') }} €</td>
                <td class="number">{{ number_format($billing->total_costs, 2, ',', '.') }} €</td>
            </tr>
            @endif
        </tbody>
    </table>


    <!-- Gesamtergebnis prominent -->
    <div style="clear: both; margin: 44px 0; text-align: center;">
        <div style="display: inline-block; background: #f0f8ff; color: black; padding: 5px 30px; border-radius: 5px; font-size: 14pt; font-weight: bold;">
            @if($billing->net_amount < 0)
                Ihre Gutschrift beträgt: {{ number_format(abs($billing->net_amount), 2, ',', '.') }} €
            @else
                Ihre Rechnungssumme beträgt: {{ number_format($billing->net_amount, 2, ',', '.') }} €
            @endif
        </div>
    </div>

    <!-- Footer für erste Seite -->
    <div class="footer-first-page" style="margin-top: 50px;">
        <!-- Erste Zeile: Rechnungsnummer mittig mit Seitenangabe -->
        <div style="text-align: center; margin-bottom: 5px;">
            Rechnungs-Nr.: {{ $billing->invoice_number }} / Seite <span class="page-number"></span>@if(isset($totalPages) && $totalPages > 0) von {{ $totalPages }}@endif
        </div>
        
        <!-- Zeile 2: Firmeninfo -->
        <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
            {{ $companySetting->company_name }}
            @if($companySetting->full_address) | {{ $companySetting->full_address }}@endif
            @if($companySetting->phone) | {{ $companySetting->phone }}@endif
            @if($companySetting->email) | {{ $companySetting->email }}@endif
        </div>
        
        <!-- Zeile 4: Amtsgericht und Geschäftsführer -->
        <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
            @if($companySetting->formatted_commercial_register){{ $companySetting->formatted_commercial_register }}@endif
            @if($companySetting->formatted_commercial_register && $companySetting->management) | @endif
            @if($companySetting->vat_id)USt-IdNr.: {{ $companySetting->vat_id }}@endif
            @if($companySetting->management) | Geschäftsführung: {{ $companySetting->management }}@endif
        </div>
        
        <!-- Bisherige Footer-Inhalte -->
        <div class="footer-content">
            <div class="footer-section">
                @if($companySetting->bank_name)
                <strong>Bankverbindung:</strong><br>
                {{ $companySetting->bank_name }}<br>
                @if($companySetting->iban)
                IBAN: {{ $companySetting->formatted_iban }}<br>
                @endif
                @if($companySetting->bic)
                BIC: {{ $companySetting->bic }}
                @endif
                @endif
            </div>
            <div class="footer-section text-center">
                @if($companySetting->tax_number)
                Steuernr.: {{ $companySetting->tax_number }}
                @endif
            </div>
            <div class="footer-section text-right">
                &nbsp;
            </div>
        </div>
    </div>

    <!-- Aufschlüsselung der Gutschriften/Einnahmen -->
    @if(!empty($billing->credit_breakdown))
    <div class="breakdown" style="page-break-before: always;">
        <h3 style="color: #16a34a; border-bottom: 2px solid #16a34a; margin-bottom: 20px;">💰 Aufschlüsselung der Einnahmen/Gutschriften</h3>
        
        <!-- Zusammenfassung -->
        <div style="background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 15px; margin-bottom: 25px; text-align: center;">
            <div style="font-size: 12pt; font-weight: bold; color: #16a34a; margin-bottom: 5px;">
                Gesamte Einnahmen/Gutschriften
            </div>
            <div style="font-size: 18pt; font-weight: bold; color: #15803d;">
                {{ number_format($billing->total_credits, 2, ',', '.') }} €
            </div>
            <div style="font-size: 8pt; color: #16a34a; margin-top: 5px;">
                ({{ number_format($currentPercentage, 2, ',', '.') }}% Anteil)
            </div>
        </div>

        @foreach($billing->credit_breakdown as $index => $credit)
        <!-- Einzelner Einnahmen-Block -->
        <div style="border: 2px solid #d1fae5; border-radius: 8px; margin-bottom: 20px; overflow: hidden; page-break-inside: avoid;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #16a34a, #059669); color: white; padding: 12px 15px;">
                <div style="display: table; width: 100%;">
                    <div style="display: table-cell; width: 70%; vertical-align: middle;">
                        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 2px;">
                            {{ $credit['supplier_name'] ?? 'Unbekannt' }}
                        </div>
                        <div style="font-size: 9pt; opacity: 0.9;">
                            {{ $credit['contract_title'] ?? ($credit['contract_number'] ?? 'Unbekannt') }}
                        </div>
                        @if(isset($credit['billing_description']) && !empty($credit['billing_description']))
                        <div style="font-size: 8pt; opacity: 0.8; font-style: italic; margin-top: 3px;">
                            {{ $credit['billing_description'] }}
                        </div>
                        @endif
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <div style="font-size: 16pt; font-weight: bold;">
                            {{ number_format($credit['customer_share'] ?? 0, 2, ',', '.') }} €
                        </div>
                        <div style="font-size: 8pt; opacity: 0.8;">
                            ({{ number_format($credit['customer_percentage'] ?? 0, 2, ',', '.') }}% Anteil)
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Details -->
            <div style="background: #f0fdf4; padding: 12px 15px;">
                <div style="display: table; width: 100%; font-size: 9pt;">
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #16a34a; font-weight: bold; margin-bottom: 3px;">Netto-Betrag</div>
                        <div style="font-size: 10pt; font-weight: bold;">{{ number_format($credit['customer_share_net'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #16a34a; font-weight: bold; margin-bottom: 3px;">MwSt. ({{ number_format((($credit['vat_rate'] ?? 0.19) <= 1 ? ($credit['vat_rate'] ?? 0.19) * 100 : ($credit['vat_rate'] ?? 19)), 0, ',', '.') }}%)</div>
                        <div style="font-size: 10pt; font-weight: bold;">{{ number_format(($credit['customer_share'] ?? 0) - ($credit['customer_share_net'] ?? 0), 2, ',', '.') }} €</div>
                    </div>
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #16a34a; font-weight: bold; margin-bottom: 3px;">Brutto-Betrag</div>
                        <div style="font-size: 11pt; font-weight: bold; color: #15803d;">{{ number_format($credit['customer_share'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
        
        <!-- Artikel-Erklärungen für Einnahmen/Gutschriften -->
        @php
            $hasDetailedDescriptions = false;
            $detailedArticles = [];
            
            foreach($billing->credit_breakdown as $credit) {
                if(isset($credit['articles']) && is_array($credit['articles'])) {
                    foreach($credit['articles'] as $article) {
                        if(isset($article['detailed_description']) && !empty($article['detailed_description'])) {
                            $hasDetailedDescriptions = true;
                            $detailedArticles[] = [
                                'name' => $article['article_name'] ?? 'Unbekannter Artikel',
                                'detailed_description' => $article['detailed_description'],
                                'supplier' => $credit['supplier_name'] ?? 'Unbekannt'
                            ];
                        }
                    }
                }
            }

            $hasDetailedDescriptions = false; # nur zum Ausblenden wegen Beschreibung in der aufschlüsselung
        @endphp
        
        @if($hasDetailedDescriptions)
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #2563eb; border-radius: 0 5px 5px 0;">
            <h4 style="margin: 0 0 10px 0; color: #2563eb; font-size: 8pt;">Erklärung der Artikel</h4>
            @foreach($detailedArticles as $article)
            <div style="margin-bottom: 12px; padding-bottom: 12px; {{ !$loop->last ? 'border-bottom: 1px solid #e6f3ff;' : '' }}">
                <strong style="color: #374151; font-size: 8pt;">{{ $article['name'] }}</strong>
                <div style="margin-top: 5px; color: #4b5563; font-size: 8pt; line-height: 1.5;">
                    {!! nl2br(e($article['detailed_description'])) !!}
                </div>
                @if($article['supplier'])
                <div style="margin-top: 3px; font-size: 7pt; color: #6b7280; font-style: italic;">
                    Lieferant: {{ $article['supplier'] }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <!-- Aufschlüsselung der Kosten -->
    @if(!empty($billing->cost_breakdown))
    <div class="breakdown" style="page-break-before: always;">
        <h3 style="color: #dc2626; border-bottom: 2px solid #dc2626; margin-bottom: 20px;">📊 Aufschlüsselung der Kosten</h3>
        
        <!-- Zusammenfassung -->
        <div style="background: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 15px; margin-bottom: 25px; text-align: center;">
            <div style="font-size: 12pt; font-weight: bold; color: #dc2626; margin-bottom: 5px;">
                Gesamte Kosten
            </div>
            <div style="font-size: 18pt; font-weight: bold; color: #b91c1c;">
                {{ number_format($billing->total_costs, 2, ',', '.') }} €
            </div>
            <div style="font-size: 8pt; color: #dc2626; margin-top: 5px;">
                ({{ number_format($currentPercentage, 2, ',', '.') }}% Anteil)
            </div>
        </div>

        @foreach($billing->cost_breakdown as $index => $cost)
        <!-- Einzelner Kosten-Block -->
        <div style="border: 2px solid #fecaca; border-radius: 8px; margin-bottom: 20px; overflow: hidden; page-break-inside: avoid;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 12px 15px;">
                <div style="display: table; width: 100%;">
                    <div style="display: table-cell; width: 70%; vertical-align: middle;">
                        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 2px;">
                            {{ $cost['supplier_name'] ?? 'Unbekannt' }}
                        </div>
                        <div style="font-size: 9pt; opacity: 0.9;">
                            {{ $cost['contract_title'] ?? ($cost['contract_number'] ?? 'Unbekannt') }}
                        </div>
                        @if(isset($cost['billing_description']) && !empty($cost['billing_description']))
                        <div style="font-size: 8pt; opacity: 0.8; font-style: italic; margin-top: 3px;">
                            {{ $cost['billing_description'] }}
                        </div>
                        @endif
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <div style="font-size: 16pt; font-weight: bold;">
                            {{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }} €
                        </div>
                        <div style="font-size: 8pt; opacity: 0.8;">
                            ({{ number_format($cost['customer_percentage'] ?? 0, 2, ',', '.') }}% Anteil)
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Details -->
            <div style="background: #fef2f2; padding: 12px 15px;">
                <div style="display: table; width: 100%; font-size: 9pt;">
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #dc2626; font-weight: bold; margin-bottom: 3px;">Netto-Betrag</div>
                        <div style="font-size: 10pt; font-weight: bold;">{{ number_format($cost['customer_share_net'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #dc2626; font-weight: bold; margin-bottom: 3px;">MwSt. ({{ number_format((($cost['vat_rate'] ?? 0.19) <= 1 ? ($cost['vat_rate'] ?? 0.19) * 100 : ($cost['vat_rate'] ?? 19)), 0, ',', '.') }}%)</div>
                        <div style="font-size: 10pt; font-weight: bold;">{{ number_format(($cost['customer_share'] ?? 0) - ($cost['customer_share_net'] ?? 0), 2, ',', '.') }} €</div>
                    </div>
                    <div style="display: table-cell; width: 33.33%; text-align: center;">
                        <div style="color: #dc2626; font-weight: bold; margin-bottom: 3px;">Brutto-Betrag</div>
                        <div style="font-size: 11pt; font-weight: bold; color: #b91c1c;">{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- MwSt.-Aufschlüsselung -->
    <div style="margin-top: 30px; background: #e6f3ff; color: black; padding: 15px; border-radius: 5px;">
        <div style="display: table; width: 100%; font-size: 9pt;">
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 1px 0;">
                    Gesamtsumme 
                    @if($billing->net_amount < 0) der Gutschrift @else der Rechnung @endif
                    netto:
                </div>
                <div style="display: table-cell; text-align: right; padding: 1px 0;">
                    {{ number_format(abs(($billing->total_costs_net ?? 0) - ($billing->total_credits_net ?? 0)), 2, ',', '.') }} €
                </div>
            </div>
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 1px 0;">Zzgl. MwSt. von 19%:</div>
                <div style="display: table-cell; text-align: right; padding: 1px 0;">
                    {{ number_format(abs($billing->total_vat_amount ?? 0), 2, ',', '.') }} €
                </div>
            </div>
            <div style="display: table-row; border-top: 1px solid rgba(255,255,255,0.3);">
                <div style="display: table-cell; padding: 1px 0 1px 0; font-weight: bold; font-size: 9pt;">
                    Gesamtsumme 
                    @if($billing->net_amount < 0) der Gutschrift @else der Rechnung @endif
                    brutto:
                </div>
                <div style="display: table-cell; text-align: right; padding: 1px 0 3px 0; font-weight: bold; font-size: 9pt;">
                    {{ number_format(abs($billing->net_amount ?? 0), 2, ',', '.') }} €
                </div>
            </div>
        </div>
    </div>

    <!-- Bemerkung -->
    @if($billing->notes)
    <div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #2563eb; border-radius: 0 5px 5px 0;">
        <div style="font-size: 9pt; color: #374151; line-height: 1.4;">
            {!! nl2br(e($billing->notes)) !!}
        </div>
    </div>
    @endif

    <!-- Hinweise -->
    @if($billing->show_hints ?? true)
    <div style="margin-top: 20px; font-size: 9pt; color: #666; page-break-inside: avoid;">
        <p><strong>Hinweise:</strong></p>
        <ul>
            <li>Diese Abrechnung zeigt Ihren Anteil an den Einnahmen und Kosten der Solaranlage {{ $solarPlant->name }}.</li>
            <li>Ihr aktueller Beteiligungsanteil beträgt {{ number_format($currentPercentage, 2, ',', '.') }}%.</li>
            <li>Die Abrechnung der Marktprämie erfolgt Umsatzsteuerfrei.</li>
            @if($billing->total_credits > 0)
            <li>Die Einnahmen/Gutschriften stammen aus Vertragsabrechnungen unserer Lieferanten für diese Solaranlage.</li>
            @endif
            <li>Bei Fragen zu dieser Abrechnung wenden Sie sich bitte an uns.</li>
        </ul>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <!-- Erste Zeile: Rechnungsnummer mittig mit Seitenangabe -->
        <div style="text-align: center; margin-bottom: 5px;">
            Rechnungs-Nr.: {{ $billing->invoice_number }} / Seite <span class="page-number"></span>@if(isset($totalPages) && $totalPages > 0) von {{ $totalPages }}@endif
        </div>
        
        <!-- Zeile 2: Firmeninfo -->
        <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
            {{ $companySetting->company_name }}
            @if($companySetting->full_address) | {{ $companySetting->full_address }}@endif
            @if($companySetting->phone) | {{ $companySetting->phone }}@endif
            @if($companySetting->email) | {{ $companySetting->email }}@endif
        </div>
        
        <!-- Zeile 4: Amtsgericht und Geschäftsführer -->
        <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
            @if($companySetting->formatted_commercial_register){{ $companySetting->formatted_commercial_register }}@endif
            @if($companySetting->formatted_commercial_register && $companySetting->management) | @endif
            @if($companySetting->vat_id)USt-IdNr.: {{ $companySetting->vat_id }}@endif
            @if($companySetting->management) | Geschäftsführung: {{ $companySetting->management }}@endif
        </div>
        
        <!-- Bisherige Footer-Inhalte -->
        <div class="footer-content">
            <div class="footer-section">
                @if($companySetting->bank_name)
                <strong>Bankverbindung:</strong><br>
                {{ $companySetting->bank_name }}<br>
                @if($companySetting->iban)
                IBAN: {{ $companySetting->formatted_iban }}<br>
                @endif
                @if($companySetting->bic)
                BIC: {{ $companySetting->bic }}
                @endif
                @endif
            </div>
            <div class="footer-section text-center">
                @if($companySetting->tax_number)
                Steuernr.: {{ $companySetting->tax_number }}
                @endif
            </div>
            <div class="footer-section text-right">
                &nbsp;
            </div>
        </div>
    </div>
</body>
</html>
