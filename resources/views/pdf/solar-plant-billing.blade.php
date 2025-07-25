<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solaranlagen-Abrechnung</title>
    <style>
        @page {
            margin: 2cm 1.5cm;
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
            margin-top: -80px;
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
            color: #666;
        }
        
        .plant-info {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        
        .plant-info h3 {
            margin: 0 0 10px 0;
            color: #2563eb;
        }
        
        .plant-details {
            display: table;
            width: 100%;
        }
        
        .plant-details > div {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
        }
        
        .positions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .positions-table th {
            background: #2563eb;
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
            font-family: 'Courier New', monospace;
        }
        
        .totals {
            float: right;
            width: 300px;
            margin: 20px 0;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .totals .total-row {
            font-weight: bold;
            font-size: 12pt;
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
            font-family: 'Courier New', monospace;
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
            @if($companySetting->hasLogo())
                <img src="{{ public_path('storage/' . $companySetting->logo_path) }}" alt="Logo">
            @endif
        </div>
        <div class="company-info">
            <h1>{{ $companySetting->full_company_name }}</h1>
            <div>{{ $companySetting->full_address }}</div>
            @if($companySetting->phone)
                <div>Tel: {{ $companySetting->phone }}</div>
            @endif
            @if($companySetting->email)
                <div>E-Mail: {{ $companySetting->email }}</div>
            @endif
            @if($companySetting->website)
                <div>Web: {{ $companySetting->website }}</div>
            @endif
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
        {{ $customer->address }}<br>
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
        <h2>Solaranlagen-Abrechnung</h2>
    </div>

    <div class="billing-period">
        Abrechnungsperiode: {{ $monthName }} {{ $billing->billing_year }}
    </div>

    <!-- Anlageninfo -->
    <div class="plant-info">
        <h3>Solaranlage: {{ $solarPlant->plant_number }}</h3>
        <div class="plant-details">
            <div>
                <strong>Standort:</strong><br>
                {{ $solarPlant->address }}<br>
                {{ $solarPlant->postal_code }} {{ $solarPlant->city }}
            </div>
            <div>
                <strong>Anlagenleistung:</strong><br>
                {{ number_format($solarPlant->capacity_kwp, 2, ',', '.') }} kWp
            </div>
            <div>
                <strong>Ihre Beteiligung:</strong><br>
                {{ number_format($currentPercentage, 2, ',', '.') }}%
            </div>
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
            <!-- Einnahmen -->
            @if($billing->revenue_amount > 0)
            <tr>
                <td>1</td>
                <td>
                    <strong>Einnahmen aus Direktvermarktung</strong><br>
                    <small>{{ $monthName }} {{ $billing->billing_year }} - {{ number_format($currentPercentage, 2, ',', '.') }}% Anteil</small>
                </td>
                <td class="number">{{ number_format($billing->energy_kwh, 0, ',', '.') }}</td>
                <td>kWh</td>
                <td class="number">{{ number_format($billing->revenue_amount / $billing->energy_kwh, 4, ',', '.') }} €</td>
                <td class="number">{{ number_format($billing->revenue_amount, 2, ',', '.') }} €</td>
            </tr>
            @endif

            <!-- Kosten -->
            @if($billing->cost_amount > 0)
            <tr>
                <td>2</td>
                <td>
                    <strong>Betriebskosten</strong><br>
                    <small>Anteilige Kosten für {{ $monthName }} {{ $billing->billing_year }}</small>
                </td>
                <td class="number">1</td>
                <td>Monat</td>
                <td class="number">{{ number_format($billing->cost_amount, 2, ',', '.') }} €</td>
                <td class="number">-{{ number_format($billing->cost_amount, 2, ',', '.') }} €</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Summen -->
    <div class="totals">
        <table>
            @if($billing->revenue_amount > 0)
            <tr>
                <td>Einnahmen:</td>
                <td class="number">{{ number_format($billing->revenue_amount, 2, ',', '.') }} €</td>
            </tr>
            @endif
            @if($billing->cost_amount > 0)
            <tr>
                <td>Kosten:</td>
                <td class="number">-{{ number_format($billing->cost_amount, 2, ',', '.') }} €</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Gesamtergebnis:</td>
                <td class="number">{{ number_format($billing->net_amount, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    <!-- Aufschlüsselung der Einnahmen -->
    @if($billing->billingBreakdowns->where('type', 'revenue')->count() > 0)
    <div class="breakdown">
        <h3>Aufschlüsselung der Einnahmen</h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Zeitraum</th>
                    <th>Energiemenge</th>
                    <th>Anlagenanteil</th>
                    <th>Kundenanteil</th>
                    <th class="number">Betrag (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billing->billingBreakdowns->where('type', 'revenue') as $breakdown)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($breakdown->period_start)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($breakdown->period_end)->format('d.m.Y') }}</td>
                    <td class="number">{{ number_format($breakdown->energy_kwh, 0, ',', '.') }} kWh</td>
                    <td class="number">{{ number_format($breakdown->solar_plant_percentage, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($breakdown->customer_percentage, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($breakdown->amount, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Aufschlüsselung der Kosten -->
    @if($billing->billingBreakdowns->where('type', 'cost')->count() > 0)
    <div class="breakdown">
        <h3>Aufschlüsselung der Kosten</h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Zeitraum</th>
                    <th>Beschreibung</th>
                    <th>Anlagenanteil</th>
                    <th>Kundenanteil</th>
                    <th class="number">Betrag (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billing->billingBreakdowns->where('type', 'cost') as $breakdown)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($breakdown->period_start)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($breakdown->period_end)->format('d.m.Y') }}</td>
                    <td>{{ $breakdown->description ?: 'Betriebskosten' }}</td>
                    <td class="number">{{ number_format($breakdown->solar_plant_percentage, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($breakdown->customer_percentage, 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($breakdown->amount, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Hinweise -->
    <div style="margin-top: 30px; font-size: 9pt; color: #666;">
        <p><strong>Hinweise:</strong></p>
        <ul>
            <li>Diese Abrechnung zeigt Ihren Anteil an den Einnahmen und Kosten der Solaranlage {{ $solarPlant->plant_number }}.</li>
            <li>Ihr aktueller Beteiligungsanteil beträgt {{ number_format($currentPercentage, 2, ',', '.') }}%.</li>
            <li>Die Einnahmen stammen aus der Direktvermarktung des erzeugten Solarstroms.</li>
            @if($billing->cost_amount > 0)
            <li>Die Kosten beinhalten anteilige Betriebskosten, Wartung und Verwaltung der Anlage.</li>
            @endif
            <li>Bei Fragen zu dieser Abrechnung wenden Sie sich bitte an uns.</li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="footer">
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
                Steuernr.: {{ $companySetting->tax_number }}<br>
                @endif
                @if($companySetting->formatted_commercial_register)
                {{ $companySetting->formatted_commercial_register }}<br>
                @endif
                @if($companySetting->management)
                Geschäftsführung: {{ $companySetting->management }}
                @endif
            </div>
            <div class="footer-section text-right">
                Seite <span class="page-number"></span> von <span class="total-pages"></span><br>
                Erstellt am {{ $generatedAt->format('d.m.Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
