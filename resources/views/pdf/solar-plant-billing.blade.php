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
            margin-bottom: 0px;
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
            margin-top: -40px;
        }
        
        .recipient h3 {
            margin: 0 0 5px 0;
            font-size: 11pt;
            color: #555;
        }
        
        .billing-info {
            float: right;
            width: 45%;
            margin-top: -190px;
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
            #background: #96989aff;
            color: black;
            padding: 0px 8px;
            text-align: left;
            #font-weight: bold;
            border-bottom: 1px solid #000000;
        }
        
        .positions-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        /* Alternating row backgrounds removed for German tax office compliance */
        
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
                             style="@if($companySetting->logo_styles){{ $companySetting->logo_styles }}; @else max-width: 150px; max-height: 60px; @endif object-fit: contain;">
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
                <td>Belegnummer:</td>
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
     <!--
    <div class="document-title">
        
    </div>-->
    
    <div class="billing-period">
        <h3>Gutschrift für Ihre Einspeisung {{ $monthName }} {{ $billing->billing_year }}</h3>
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

    <!-- Detaillierte Positionsaufstellung für finanzamtskonforme Gutschrift -->
    <table class="positions-table">
        <thead>
            <tr>
                <th>Pos.</th>
                <th>Beschreibung</th>
                <th class="number">Netto €</th>
                <th class="number">USt. € (Satz)</th>
                <th class="number">Brutto €</th>
            </tr>
        </thead>
        <tbody>
            @php
                $positionCounter = 1;
            @endphp
            
            <!-- Detaillierte Auflistung der Gutschriften/Einnahmen -->
            @if(!empty($billing->credit_breakdown))
                @foreach($billing->credit_breakdown as $credit)
                <tr>
                    <td><small>{{ $positionCounter++ }}</small></td>
                    <td>
                        <small>{{ $credit['contract_title'] ?? 'Einnahmen/Gutschriften' }}</small><br>
                        <small style="">{{ $credit['supplier_name'] ?? 'Unbekannt' }}</small>
                    </td>
                    <td class="number"><small>{{ number_format(abs($credit['customer_share_net'] ?? 0), 2, ',', '.') }}</small></td>
                    <td class="number">
                        <small>{{ number_format(abs(($credit['customer_share'] ?? 0) - ($credit['customer_share_net'] ?? 0)), 2, ',', '.') }} € ({{ number_format((($credit['vat_rate'] ?? 0.19) <= 1 ? ($credit['vat_rate'] ?? 0.19) * 100 : ($credit['vat_rate'] ?? 19)), 0, ',', '.') }}%)</small>
                    </td>
                    <td class="number"><small>{{ number_format(abs($credit['customer_share'] ?? 0), 2, ',', '.') }}</small></td>
                </tr>
                @endforeach
            @elseif($billing->total_credits > 0)
                <!-- Fallback wenn keine detaillierte Aufschlüsselung vorhanden -->
                <tr>
                    <td>{{ $positionCounter++ }}</td>
                    <td>
                        <strong>Einnahmen/Gutschriften</strong><br>
                        <small>{{ $monthName }} {{ $billing->billing_year }} - {{ number_format($currentPercentage, 2, ',', '.') }}% Anteil</small><br>
                        <small style="color: #888;">Sammelposten</small>
                    </td>
                    <td class="number">{{ number_format(abs($billing->total_credits_net ?? $billing->total_credits), 2, ',', '.') }}</td>
                    <td class="number">
                        19%<br>
                        <small>{{ number_format(abs(($billing->total_credits) - ($billing->total_credits_net ?? $billing->total_credits)), 2, ',', '.') }} €</small>
                    </td>
                    <td class="number">{{ number_format(abs($billing->total_credits), 2, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Detaillierte Auflistung der Kosten -->
            @if(!empty($billing->cost_breakdown))
                @foreach($billing->cost_breakdown as $cost)
                <tr style="background: #ffffff;">
                    <td><small>{{ $positionCounter++ }}</small></td>
                    <td>
                        <small>{{ $cost['contract_title'] ?? 'Betriebskosten' }}</small><br>
                        <small style="">{{ $cost['supplier_name'] ?? 'Unbekannt' }}</small>
                    </td>
                    <td class="number"><small>{{ number_format(-abs($cost['customer_share_net'] ?? 0), 2, ',', '.') }}</small></td>
                    <td class="number">
                        @php
                            $netAmount = $cost['customer_share_net'] ?? 0;
                            $grossAmount = $cost['customer_share'] ?? 0;
                            $taxAmount = $grossAmount - $netAmount;
                        @endphp
                        <small>{{ number_format($taxAmount, 2, ',', '.') }} € ({{ number_format((($cost['vat_rate'] ?? 0.19) <= 1 ? ($cost['vat_rate'] ?? 0.19) * 100 : ($cost['vat_rate'] ?? 19)), 0, ',', '.') }}%)</small>
                    </td>
                    <td class="number"><small>{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }}</small></td>
                </tr>
                @endforeach
            @elseif($billing->total_costs > 0 || isset($billing->total_costs))
                <!-- Fallback wenn keine detaillierte Aufschlüsselung vorhanden -->
                <tr style="background: #fff8f8;">
                    <td>{{ $positionCounter++ }}</td>
                    <td>
                        <strong>Betriebskosten</strong><br>
                        <small>{{ $monthName }} {{ $billing->billing_year }} - {{ number_format($currentPercentage, 2, ',', '.') }}% Anteil</small><br>
                        <small style="color: #888;">Sammelposten</small>
                    </td>
                    <td class="number">-{{ number_format(($billing->total_costs_net ?? abs($billing->total_costs ?? 0)), 2, ',', '.') }}</td>
                    <td class="number">
                        19%<br>
                        <small>-{{ number_format((abs($billing->total_costs ?? 0)) - ($billing->total_costs_net ?? abs($billing->total_costs ?? 0)), 2, ',', '.') }} €</small>
                    </td>
                    <td class="number">-{{ number_format(abs($billing->total_costs ?? 0), 2, ',', '.') }}</td>
                </tr>
            @endif

            @php
            // Sammle alle verschiedenen Steuersätze und berechne Gesamtbeträge
            $taxBreakdown = [];
            $totalNet = 0;
            $totalTax = 0;
            $totalGross = 0;
            
            // Gutschriften/Einnahmen durchgehen
            if (!empty($billing->credit_breakdown)) {
                foreach ($billing->credit_breakdown as $credit) {
                    $vatRate = $credit['vat_rate'] ?? 0.19;
                    $vatRatePercent = ($vatRate <= 1 ? $vatRate * 100 : $vatRate);
                    $netAmount = $credit['customer_share_net'] ?? 0;
                    $grossAmount = $credit['customer_share'] ?? 0;
                    $taxAmount = $grossAmount - $netAmount;
                    
                    if (!isset($taxBreakdown[$vatRatePercent])) {
                        $taxBreakdown[$vatRatePercent] = ['net' => 0, 'tax' => 0, 'gross' => 0];
                    }
                    
                    $taxBreakdown[$vatRatePercent]['net'] += $netAmount;
                    $taxBreakdown[$vatRatePercent]['tax'] += $taxAmount;
                    $taxBreakdown[$vatRatePercent]['gross'] += $grossAmount;
                    
                    $totalNet += $netAmount;
                    $totalTax += $taxAmount;
                    $totalGross += $grossAmount;
                }
            }
            
            // Kosten durchgehen (als negative Werte)
            if (!empty($billing->cost_breakdown)) {
                foreach ($billing->cost_breakdown as $cost) {
                    $vatRate = $cost['vat_rate'] ?? 0.19;
                    $vatRatePercent = ($vatRate <= 1 ? $vatRate * 100 : $vatRate);
                    $netAmount = -($cost['customer_share_net'] ?? 0);
                    $grossAmount = -($cost['customer_share'] ?? 0);
                    $taxAmount = $grossAmount - $netAmount;
                    
                    if (!isset($taxBreakdown[$vatRatePercent])) {
                        $taxBreakdown[$vatRatePercent] = ['net' => 0, 'tax' => 0, 'gross' => 0];
                    }
                    
                    $taxBreakdown[$vatRatePercent]['net'] += $netAmount;
                    $taxBreakdown[$vatRatePercent]['tax'] += $taxAmount;
                    $taxBreakdown[$vatRatePercent]['gross'] += $grossAmount;
                    
                    $totalNet += $netAmount;
                    $totalTax += $taxAmount;
                    $totalGross += $grossAmount;
                }
            }
            
            // Sortiere nach Steuersatz
            ksort($taxBreakdown);
        @endphp

            <tr style="background: #f0f8ff;">
                <td colspan="4"><strong>
                    @if($billing->net_amount < 0)
                        Guthaben
                    @else
                        Rechnungssumme
                    @endif
                    </strong>
                </td>
                <td class="number"><strong>{{ number_format(abs($totalGross ?: ($billing->net_amount ?? 0)), 2, ',', '.') }} €</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer für erste Seite -->
    <div class="footer-first-page" style="margin-top: 50px;">
        <!-- Erste Zeile: Rechnungsnummer mittig mit Seitenangabe -->
        <div style="text-align: center; margin-bottom: 5px;">
            Belegnummer: {{ $billing->invoice_number }} / Seite <span class="page-number"></span>@if(isset($totalPages) && $totalPages > 0) von {{ $totalPages }}@endif
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
        <h3>Aufschlüsselung der Einnahmen/Gutschriften</h3>
        <table class="breakdown-table">

            <tbody>
                @foreach($billing->credit_breakdown as $credit)
                <tr><td colspan="5"></td></tr>
                <tr style="background: #f0f8ff; page-break-inside: avoid; page-break-after: avoid;">
                    <td colspan="5" style="padding: 8px; page-break-inside: avoid;">
                        <!-- Lieferant - Zeile 1 -->
                        <div style="font-weight: bold; font-size: 10pt; color: #333; margin-bottom: 3px;">
                            {{ $credit['supplier_name'] ?? 'Unbekannt' }}
                        </div>
                        
                        <!-- Contract Title - Zeile 2 -->
                        <div style="font-size: 9pt; color: #666; margin-bottom: 3px;">
                            {{ $credit['contract_title'] ?? ($credit['contract_number'] ?? 'Unbekannt') }}
                        </div>
                        
                        <!-- Billing Description - Zeile 3 -->
                        @if(isset($credit['billing_description']) && !empty($credit['billing_description']))
                        <div style="font-size: 8pt; color: #888; margin-bottom: 8px; font-style: italic;">
                            {{ $credit['billing_description'] }}
                        </div>
                        @else
                        <div style="margin-bottom: 5px;"></div>
                        @endif
                        
                        <!-- Werte-Tabelle -->
                        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                            <thead>
                                <tr>
                                    <th style="text-align: center; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Ihr Anteil</th>
                                    <th style="text-align: right; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Netto (€)</th>
                                    <th style="text-align: center; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">USt.</th>
                                    <th style="text-align: right; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Gesamtbetrag Brutto (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="text-align: center; padding: 4px 8px;">{{ number_format($credit['customer_percentage'] ?? 0, 2, ',', '.') }}%</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format(abs($credit['customer_share_net'] ?? 0), 2, ',', '.') }}</td>
                                    <td style="text-align: center; padding: 4px 8px;">{{ number_format((($credit['vat_rate'] ?? 0.19) <= 1 ? ($credit['vat_rate'] ?? 0.19) * 100 : ($credit['vat_rate'] ?? 19)), 0, ',', '.') }}%</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format(abs($credit['customer_share'] ?? 0), 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <!--
                <tr>
                    <td colspan="5" style="background: #e6f3ff; color: #666; padding: 8px; font-size: 9pt;">
                        {{ $credit['contract_title'] ?? 'Einnahmen/Gutschriften' }} - {{ $credit['supplier_name'] ?? 'Unbekannt' }}
                    </td>
                </tr>-->
                @if(isset($credit['articles']) && !empty($credit['articles']))
                <tr style="page-break-inside: avoid; page-break-before: avoid;">
                    <td colspan="5" style="padding-left: 10px; background: #f8f9fa; border-top: none; page-break-inside: avoid;">
                        <strong>Artikel-Aufschlüsselung:</strong>
                        <div style="margin-top: 8px;">
                            @foreach($credit['articles'] as $article)
                            @php
                                // Lade Artikel-Model um Nachkommastellen-Einstellungen zu erhalten
                                $articleModel = null;
                                $decimalPlaces = 2;
                                $totalDecimalPlaces = 2;
                                
                                if (isset($article['article_id']) && $article['article_id']) {
                                    $articleModel = \App\Models\Article::find($article['article_id']);
                                    if ($articleModel) {
                                        $decimalPlaces = $articleModel->getDecimalPlaces();
                                        $totalDecimalPlaces = $articleModel->getTotalDecimalPlaces();
                                    }
                                }
                                
                                // Berechne Steuer und Brutto-Betrag
                                $netPrice = $article['total_price_net'] ?? 0;
                                $taxRate = $article['tax_rate'] ?? 0.19;
                                $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                            @endphp
                            <div style="margin-bottom: 12px; padding: 8px; border: 1px solid #e6e6e6; border-radius: 3px; background: #fff;">
                                <!-- Artikel Name -->
                                <div style="font-weight: bold; font-size: 8pt; color: #333; margin-bottom: 3px;">
                                    {{ $article['article_name'] ?? 'Unbekannt' }}
                                </div>
                                
                                <!-- Artikel Beschreibung (falls vorhanden und unterschiedlich) -->
                                @if(isset($article['description']) && $article['description'] !== $article['article_name'] && !empty($article['description']))
                                <div style="font-size: 8pt; color: #666; margin-bottom: 12px;">
                                    {{ $article['description'] }}
                                </div>
                                @endif
                                
                                <!-- Details als Tabelle -->
                                <table style="width: 100%; border-collapse: collapse; font-size: 8pt; color: #555;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Menge</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Einzelpreis</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Gesamtpreis (netto)</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Steuer ({{ number_format(($taxRate <= 1 ? $taxRate * 100 : $taxRate), 1, ',', '.') }}%)</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Gesamtbetrag (brutto)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="text-align: left; padding: 3px 6px;">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($taxAmount, 2, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($grossPrice, 2, ',', '.') }} €</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div style="margin-top: 5px; color: #4b5563; font-size: 8pt; line-height: 1.5;">
                                    <b>Hinweis:</b><br>
                                    {!! nl2br(e($article['detailed_description'])) !!}
                                </div>

                            </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        
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
        <h3>Aufschlüsselung der Kosten</h3>
        <table class="breakdown-table">

            <tbody>
                @foreach($billing->cost_breakdown as $cost)
                <tr><td colspan="5"></td></tr>
                <tr style="background: #f0f8ff; page-break-inside: avoid; page-break-after: avoid;">
                    <td colspan="5" style="padding: 8px; page-break-inside: avoid;">
                        <!-- Lieferant - Zeile 1 -->
                        <div style="font-weight: bold; font-size: 10pt; color: #333; margin-bottom: 3px;">
                            {{ $cost['supplier_name'] ?? 'Unbekannt' }}
                        </div>
                        
                        <!-- Contract Title - Zeile 2 -->
                        <div style="font-size: 9pt; color: #666; margin-bottom: 3px;">
                            {{ $cost['contract_title'] ?? ($cost['contract_number'] ?? 'Unbekannt') }}
                        </div>
                        
                        <!-- Billing Description - Zeile 3 -->
                        @if(isset($cost['billing_description']) && !empty($cost['billing_description']))
                        <div style="font-size: 8pt; color: #888; margin-bottom: 8px; font-style: italic;">
                            {{ $cost['billing_description'] }}
                        </div>
                        @else
                        <div style="margin-bottom: 5px;"></div>
                        @endif
                        
                        <!-- Werte-Tabelle -->
                        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                            <thead>
                                <tr>
                                    <th style="text-align: center; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Ihr Anteil</th>
                                    <th style="text-align: right; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Netto (€)</th>
                                    <th style="text-align: center; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">MwSt.</th>
                                    <th style="text-align: right; padding: 4px 8px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa;">Gesamtbetrag Brutto (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="text-align: center; padding: 4px 8px;">{{ number_format($cost['customer_percentage'] ?? 0, 2, ',', '.') }}%</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($cost['customer_share_net'] ?? 0, 2, ',', '.') }}</td>
                                    <td style="text-align: center; padding: 4px 8px;">{{ number_format((($cost['vat_rate'] ?? 0.19) <= 1 ? ($cost['vat_rate'] ?? 0.19) * 100 : ($cost['vat_rate'] ?? 19)), 0, ',', '.') }}%</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                @if(isset($cost['articles']) && !empty($cost['articles']))
                <tr style="page-break-inside: avoid; page-break-before: avoid;">
                    <td colspan="5" style="padding-left: 15px; background: #f8f9fa; border-top: none; page-break-inside: avoid;">
                        <strong>Artikel-Aufschlüsselung:</strong>
                        <div style="margin-top: 8px;">
                            @foreach($cost['articles'] as $article)
                            @php
                                // Lade Artikel-Model um Nachkommastellen-Einstellungen zu erhalten
                                $articleModel = null;
                                $decimalPlaces = 2;
                                $totalDecimalPlaces = 2;
                                
                                if (isset($article['article_id']) && $article['article_id']) {
                                    $articleModel = \App\Models\Article::find($article['article_id']);
                                    if ($articleModel) {
                                        $decimalPlaces = $articleModel->getDecimalPlaces();
                                        $totalDecimalPlaces = $articleModel->getTotalDecimalPlaces();
                                    }
                                }
                                
                                // Berechne Steuer und Brutto-Betrag
                                $netPrice = $article['total_price_net'] ?? 0;
                                $taxRate = $article['tax_rate'] ?? 0.19;
                                $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                            @endphp
                            <div style="margin-bottom: 12px; padding: 8px; border: 1px solid #e6e6e6; border-radius: 3px; background: #fff;">
                                <!-- Artikel Name -->
                                <div style="font-weight: bold; font-size: 8pt; color: #333; margin-bottom: 3px;">
                                    {{ $article['article_name'] ?? 'Unbekannt' }}
                                </div>
                                
                                <!-- Artikel Beschreibung (falls vorhanden und unterschiedlich) -->
                                @if(isset($article['description']) && $article['description'] !== $article['article_name'] && !empty($article['description']))
                                <div style="font-size: 7pt; color: #666; margin-bottom: 6px; font-style: italic;">
                                    {{ $article['description'] }}
                                </div>
                                @endif
                                
                                <!-- Details als Tabelle -->
                                <table style="width: 100%; border-collapse: collapse; font-size: 7pt; color: #555;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Menge</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Einzelpreis</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Gesamtpreis (netto)</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Steuer ({{ number_format(($taxRate <= 1 ? $taxRate * 100 : $taxRate), 1, ',', '.') }}%)</th>
                                            <th style="text-align: right; padding: 3px 6px; border-bottom: 1px solid #ddd; font-weight: bold;">Gesamtbetrag (brutto)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="text-align: left; padding: 3px 6px;">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($taxAmount, 2, ',', '.') }} €</td>
                                            <td style="text-align: right; padding: 3px 6px;">{{ number_format($grossPrice, 2, ',', '.') }} €</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div style="margin-top: 5px; color: #4b5563; font-size: 8pt; line-height: 1.5;">
                                    <b>Hinweis:</b><br>
                                    {!! nl2br(e($article['detailed_description'])) !!}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        
        <!-- Artikel-Erklärungen für Kosten -->
        @php
            $hasCostDetailedDescriptions = false;
            $costDetailedArticles = [];
            
            foreach($billing->cost_breakdown as $cost) {
                if(isset($cost['articles']) && is_array($cost['articles'])) {
                    foreach($cost['articles'] as $article) {
                        if(isset($article['detailed_description']) && !empty($article['detailed_description'])) {
                            $hasCostDetailedDescriptions = true;
                            $costDetailedArticles[] = [
                                'name' => $article['article_name'] ?? 'Unbekannter Artikel',
                                'detailed_description' => $article['detailed_description'],
                                'supplier' => $cost['supplier_name'] ?? 'Unbekannt'
                            ];
                        }
                    }
                }
            }

            $hasCostDetailedDescriptions = false; # nur zum Ausblenden wegen Beschreibung in der aufschlüsselung
        @endphp
        
        @if($hasCostDetailedDescriptions)
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #2563eb; border-radius: 0 5px 5px 0;">
            <h4 style="margin: 0 0 10px 0; color: #2563eb; font-size: 8pt;">Erklärung der Kosten-Artikel</h4>
            @foreach($costDetailedArticles as $article)
            <div style="margin-bottom: 12px; padding-bottom: 12px; {{ !$loop->last ? 'border-bottom: 1px solid #ffe6e6;' : '' }}">
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

    <!-- Bemerkung -->
    @if($billing->notes)
    <div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #2563eb; border-radius: 0 5px 5px 0; page-break-inside: avoid;">
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
            Belegnummer: {{ $billing->invoice_number }} / Seite <span class="page-number"></span>@if(isset($totalPages) && $totalPages > 0) von {{ $totalPages }}@endif
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
