<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solaranlagen-Abrechnung</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm;
            size: A4;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
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
            margin: 30px 0;
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
            border: 1px solid #ddd;
        }
        
        .breakdown-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
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
            height: 80px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 8pt;
            color: #666;
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
        
        .page-number:after {
            content: counter(page);
        }
        
        .total-pages:after {
            content: counter(pages);
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
            <h3>{{ $companySetting->company_name }}</h3>
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
                <td>Abrechnungs-Nr.:</td>
                <td>{{ $billing->id }}</td>
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
            @if($companySetting->vat_id)
            <tr>
                <td>USt-IdNr.:</td>
                <td>{{ $companySetting->vat_id }}</td>
            </tr>
            @endif
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
                @endif
            </div>
            <div>
                <strong>Ihre Beteiligung:</strong><br>
                {{ number_format($currentPercentage, 2, ',', '.') }}%
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
                <td class="number">-{{ number_format($billing->total_costs, 2, ',', '.') }} €</td>
            </tr>
            @endif
        </tbody>
    </table>


    <!-- Gesamtergebnis prominent -->
    <div style="clear: both; margin: 44px 0; text-align: center;">
        <div style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; border-radius: 5px; font-size: 14pt; font-weight: bold;">
            @if($billing->net_amount < 0)
                Gutschrift: {{ number_format(abs($billing->net_amount), 2, ',', '.') }} €
            @else
                Rechnung: {{ number_format($billing->net_amount, 2, ',', '.') }} €
            @endif
        </div>
    </div>

    <!-- Aufschlüsselung der Gutschriften/Einnahmen -->
    @if(!empty($billing->credit_breakdown))
    <div class="breakdown" style="page-break-before: always;">
        <h3>Aufschlüsselung der Einnahmen/Gutschriften</h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Lieferant</th>
                    <th class="number">Anteil</th>
                    <th class="number">Netto (€)</th>
                    <th class="number">MwSt. %</th>
                    <th class="number">Betrag (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billing->credit_breakdown as $credit)
                <tr>
                    <td><b>{{ $credit['supplier_name'] ?? 'Unbekannt' }}</b><br>{{ $credit['contract_title'] ?? ($credit['contract_number'] ?? 'Unbekannt') }}</td>
                    <td class="number">{{ number_format($credit['customer_percentage'] ?? 0, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($credit['customer_share_net'] ?? 0, 2, ',', '.') }}</td>
                    <td class="number">{{ number_format((($credit['vat_rate'] ?? 0.19) <= 1 ? ($credit['vat_rate'] ?? 0.19) * 100 : ($credit['vat_rate'] ?? 19)), 0, ',', '.') }}%</td>
                    <td class="number">{{ number_format($credit['customer_share'] ?? 0, 2, ',', '.') }}</td>
                </tr>
                @if(isset($credit['articles']) && !empty($credit['articles']))
                <tr>
                    <td colspan="5" style="padding-left: 20px; background: #f0f8ff; border-top: none;">
                        <strong>Artikel-Aufschlüsselung:</strong>
                        <table style="width: 100%; margin-top: 5px; font-size: 8pt;">
                            <thead>
                                <tr style="background: #e6f3ff;">
                                    <th style="text-align: left; padding: 3px;">Artikel</th>
                                    <th style="text-align: center; padding: 3px;">Menge</th>
                                    <th style="text-align: center; padding: 3px;">Einheit</th>
                                    <th style="text-align: right; padding: 3px;">Einzelpreis</th>
                                    <th style="text-align: right; padding: 3px;">Gesamtpreis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($credit['articles'] as $article)
                                <tr>
                                    <td style="padding: 2px;">
                                        {{ $article['article_name'] ?? 'Unbekannt' }}
                                        @if(isset($article['description']) && $article['description'] !== $article['article_name'])
                                            <br><em style="color: #666;">{{ $article['description'] }}</em>
                                        @endif
                                    </td>
                                    <td style="text-align: center; padding: 2px;">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }}</td>
                                    <td style="text-align: center; padding: 2px;">{{ $article['unit'] ?? 'Stk.' }}</td>
                                    <td style="text-align: right; padding: 2px;">{{ number_format($article['unit_price'] ?? 0, 6, ',', '.') }} €</td>
                                    <td style="text-align: right; padding: 2px;">{{ number_format($article['total_price_net'] ?? 0, 6, ',', '.') }} €</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Aufschlüsselung der Kosten -->
    @if(!empty($billing->cost_breakdown))
    <div class="breakdown">
        <h3>Aufschlüsselung der Kosten</h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Lieferant</th>
                    <th class="number">Anteil</th>
                    <th class="number">Netto (€)</th>
                    <th class="number">MwSt. %</th>
                    <th class="number">Betrag (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billing->cost_breakdown as $cost)
                <tr>
                    <td><b>{{ $cost['supplier_name'] ?? 'Unbekannt' }}</b><br>{{ $cost['contract_title'] ?? ($cost['contract_number'] ?? 'Unbekannt') }}</td>
                    <td class="number">{{ number_format($cost['customer_percentage'] ?? 0, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($cost['customer_share_net'] ?? 0, 2, ',', '.') }}</td>
                    <td class="number">{{ number_format((($cost['vat_rate'] ?? 0.19) <= 1 ? ($cost['vat_rate'] ?? 0.19) * 100 : ($cost['vat_rate'] ?? 19)), 0, ',', '.') }}%</td>
                    <td class="number">{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }}</td>
                </tr>
                @if(isset($cost['articles']) && !empty($cost['articles']))
                <tr>
                    <td colspan="4" style="padding-left: 20px; background: #fff0f0; border-top: none;">
                        <strong>Artikel-Aufschlüsselung:</strong>
                        <table style="width: 100%; margin-top: 5px; font-size: 8pt;">
                            <thead>
                                <tr style="background: #ffe6e6;">
                                    <th style="text-align: left; padding: 3px;">Artikel</th>
                                    <th style="text-align: center; padding: 3px;">Menge</th>
                                    <th style="text-align: center; padding: 3px;">Einheit</th>
                                    <th style="text-align: right; padding: 3px;">Einzelpreis</th>
                                    <th style="text-align: right; padding: 3px;">Gesamtpreis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cost['articles'] as $article)
                                <tr>
                                    <td style="padding: 2px;">
                                        {{ $article['article_name'] ?? 'Unbekannt' }}
                                        @if(isset($article['description']) && $article['description'] !== $article['article_name'])
                                            <br><em style="color: #666;">{{ $article['description'] }}</em>
                                        @endif
                                    </td>
                                    <td style="text-align: center; padding: 2px;">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }}</td>
                                    <td style="text-align: center; padding: 2px;">{{ $article['unit'] ?? 'Stk.' }}</td>
                                    <td style="text-align: right; padding: 2px;">{{ number_format($article['unit_price'] ?? 0, 6, ',', '.') }} €</td>
                                    <td style="text-align: right; padding: 2px;">{{ number_format($article['total_price_net'] ?? 0, 6, ',', '.') }} €</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- MwSt.-Aufschlüsselung -->
    <div style="margin-top: 30px; background: #e6f3ff; color: black; padding: 15px; border-radius: 5px;">
        <div style="display: table; width: 100%; font-size: 11pt;">
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 3px 0;">Gesamtsumme netto:</div>
                <div style="display: table-cell; text-align: right; padding: 3px 0; font-weight: bold;">
                    {{ number_format(($billing->total_costs_net ?? 0) - ($billing->total_credits_net ?? 0), 2, ',', '.') }} €
                </div>
            </div>
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 3px 0;">Zzgl. MwSt. von 19%:</div>
                <div style="display: table-cell; text-align: right; padding: 3px 0; font-weight: bold;">
                    {{ number_format($billing->total_vat_amount ?? 0, 2, ',', '.') }} €
                </div>
            </div>
            <div style="display: table-row; border-top: 1px solid rgba(255,255,255,0.3);">
                <div style="display: table-cell; padding: 8px 0 3px 0; font-weight: bold; font-size: 12pt;">Gesamtsumme brutto:</div>
                <div style="display: table-cell; text-align: right; padding: 8px 0 3px 0; font-weight: bold; font-size: 12pt;">
                    {{ number_format($billing->net_amount ?? 0, 2, ',', '.') }} €
                </div>
            </div>
        </div>
    </div>

    <!-- Hinweise -->
    <div style="margin-top: 20px; font-size: 9pt; color: #666;">
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

    <!-- Footer -->
    <div class="footer">
        <!-- Erste Zeile: Abrechnungsnummer mittig -->
        <div style="text-align: center; margin-bottom: 10px;">
            Abrechnung-Nr.: {{ $billing->id }}
        </div>
        
        <!-- Zeile 2: Firmeninfo -->
        <div style="text-align: center; margin-bottom: 4px; font-size: 8pt;">
            {{ $companySetting->company_name }}
            @if($companySetting->full_address) | {{ $companySetting->full_address }}@endif
            @if($companySetting->phone) | {{ $companySetting->phone }}@endif
        </div>
        
        <!-- Zeile 3: E-Mail und Website -->
        <div style="text-align: center; margin-bottom: 4px; font-size: 8pt;">
            @if($companySetting->email){{ $companySetting->email }}@endif
            @if($companySetting->email && $companySetting->website) | @endif
            @if($companySetting->website){{ $companySetting->website }}@endif
        </div>
        
        <!-- Zeile 4: Amtsgericht und Geschäftsführer -->
        <div style="text-align: center; margin-bottom: 4px; font-size: 8pt;">
            @if($companySetting->formatted_commercial_register){{ $companySetting->formatted_commercial_register }}@endif
            @if($companySetting->formatted_commercial_register && $companySetting->management) | @endif
            @if($companySetting->management)Geschäftsführung: {{ $companySetting->management }}@endif
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
