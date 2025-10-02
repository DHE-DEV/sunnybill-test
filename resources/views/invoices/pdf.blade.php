@php
    $settings = \App\Models\CompanySetting::current();
    use App\Helpers\PriceFormatter;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $record->invoice_number }}</title>
    <style>
        @page {
            margin-top: 0.50cm;
            margin-right: 1.00cm;
            margin-bottom: 0.50cm;
            margin-left: 1.00cm;
            @bottom-center {
                content: "";
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .page-content {
            padding: 20px;
            min-height: calc(100vh - 2.2cm);
        }
        
        .header {
            margin-bottom: 40px;
        }
        
        .company-info {
            text-align: right;
            margin-bottom: 15px;
        }
        
        .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: #2563eb;
        }
        
        .invoice-info {
            position: relative;
            margin-bottom: 30px;
        }
        
        .customer-address {
            margin-left: 0;
            width: 60%;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2563eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 7pt;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            width: 50%;
            margin-left: auto;
            margin-top: 20px;
        }

        .totals table {
            margin-bottom: 0;
            font-size: 7pt;
        }

        .totals th, .totals td {
            border: none;
            padding: 5px 10px;
            font-weight: normal;
        }

        .total-row {
            font-weight: bold;
            font-size: 8pt;
            border-top: 2px solid #333 !important;
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
        
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #2563eb;
            font-size: 7pt;
        }
    </style>
</head>
<body>
<div class="page-content">
    <div class="header">
        <div class="company-info">
            @if($settings->logo_path)
                <img src="{{ storage_path('app/public/' . $settings->logo_path) }}"
                     alt="{{ $settings->company_name }}"
                     style="max-width: {{ $settings->logo_width }}px;
                            max-height: {{ $settings->logo_height }}px;
                            margin-top: {{ $settings->logo_margin_top }}px;
                            margin-right: {{ $settings->logo_margin_right }}px;
                            margin-bottom: {{ $settings->logo_margin_bottom }}px;
                            margin-left: {{ $settings->logo_margin_left }}px;">
            @else
                <h1>{{ $settings->company_name }}</h1>
            @endif
        </div>
    </div>

    <div class="invoice-info" style="margin-top: 2cm;">
        <div class="customer-address">
            <strong>Rechnungsadresse:</strong><br>
            {{ $record->customer->name }}<br>
            @if($record->customer->street)
                {{ $record->customer->street }}<br>
            @endif
            @if($record->customer->postal_code || $record->customer->city)
                {{ $record->customer->postal_code }} {{ $record->customer->city }}<br>
            @endif
            @if($record->customer->country && $record->customer->country !== 'Deutschland')
                {{ $record->customer->country }}<br>
            @endif
        </div>
        
        <div class="invoice-details" style="position: absolute; right: 0px; top: calc(5px);">
            <table style="border: none; margin: 0;">
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong>Rechnungsnummer:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;">{{ $record->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong>Rechnungsdatum:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;">{{ $record->created_at->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong>Ihre Kundennummer:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;">{{ $record->customer->customer_number ?? 'N/A' }}</td>
                </tr>
                @if($record->status === 'draft')
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong style="color: #9ca3af;">Status:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;"><strong style="color: #9ca3af;">ENTWURF</strong></td>
                </tr>
                @elseif($record->status === 'canceled')
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong style="color: #dc2626;">Status:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;"><strong style="color: #dc2626;">STORNIERT</strong></td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <div class="invoice-title" style="margin-top: 2cm;">
        Rechnung {{ $record->invoice_number }}
    </div>

    <p style="margin-top: 1cm;">Sehr geehrte Damen und Herren,</p>
    <p>hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</p>

    <table>
        <thead>
            <tr>
                <th>Pos.</th>
                <th>Beschreibung</th>
                <th class="text-center">Menge</th>
                <th class="text-right">Einzelpreis</th>
                <th class="text-center" style="white-space: nowrap;">Steuer %</th>
                <th class="text-right">Steuer</th>
                <th class="text-right">Netto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->article->name }}</strong>
                    @if($item->description)
                        <br><small>{{ $item->description }}</small>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->quantity, $item->decimal_places ?? 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->unit_price, $item->article?->getDecimalPlaces() ?? 2, ',', '.') }}</td>
                <td class="text-center" style="white-space: nowrap;">{{ number_format($item->tax_rate * 100, 0) }}%</td>
                <td class="text-right">{{ number_format($item->tax_amount, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->net_amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th style="font-weight: bold;">Gesamt Netto</th>
                <td class="text-right" style="font-weight: bold;">{{ number_format($record->net_amount, 2, ',', '.') }}</td>
            </tr>
            @php
                // Gruppiere Items nach Steuersatz
                $taxGroups = $record->items->groupBy(function($item) {
                    return $item->tax_rate;
                });
            @endphp
            @foreach($taxGroups as $taxRate => $items)
            @php
                $netSum = $items->sum(function($item) { return $item->net_amount; });
                $taxSum = $items->sum(function($item) { return $item->tax_amount; });
            @endphp
            <tr>
                <th>MwSt. {{ number_format($taxRate * 100, 0) }}% von {{ number_format($netSum, 2, ',', '.') }} EUR</th>
                <td class="text-right">{{ number_format($taxSum, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <th style="font-weight: bold;">Gesamtbetrag EUR</th>
                <td class="text-right" style="font-weight: bold;">{{ number_format($record->gross_amount, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-info">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 66%; vertical-align: top; padding-right: 15px;">
                Zahlungsinformationen:<br>
                Bitte überweisen Sie den Rechnungsbetrag innerhalb von {{ $settings->default_payment_days }} Tagen auf folgendes Konto:<br><br>
                @if($settings->bank_name)
                    <strong>{{ $settings->bank_name }}:</strong><br>
                @endif
                @if($settings->iban)
                    IBAN: {{ $settings->iban }}<br>
                @endif
                @if($settings->bic)
                    BIC: {{ $settings->bic }}<br>
                @endif
                Verwendungszweck: {{ $record->invoice_number }}
            </div>
            <div style="display: table-cell; width: 34%; vertical-align: top; text-align: center;">
                @php
                    use Endroid\QrCode\QrCode;
                    use Endroid\QrCode\Writer\PngWriter;

                    // Generiere EPC QR-Code Daten für SEPA-Überweisung (EPC069-12 Standard)
                    // Bereinige Daten von Leerzeichen
                    $beneficiaryName = trim($settings->company_name ?? '');
                    $iban = str_replace(' ', '', trim($settings->iban ?? ''));
                    $bic = trim($settings->bic ?? '');
                    $amount = number_format($record->gross_amount, 2, '.', '');
                    $reference = 'Rechnung ' . $record->invoice_number . ' / Kunde ' . ($record->customer->customer_number ?? '');

                    $qrDataLines = [];
                    $qrDataLines[] = 'BCD';                     // Service Tag
                    $qrDataLines[] = '002';                     // Version
                    $qrDataLines[] = '1';                       // Character Set (1 = UTF-8)
                    $qrDataLines[] = 'SCT';                     // Identification
                    $qrDataLines[] = $bic;                      // BIC
                    $qrDataLines[] = $beneficiaryName;          // Beneficiary Name (max 70 chars)
                    $qrDataLines[] = $iban;                     // Beneficiary Account (IBAN)
                    $qrDataLines[] = 'EUR' . $amount;           // Amount (EUR + Betrag)
                    $qrDataLines[] = '';                        // Purpose (optional)
                    $qrDataLines[] = '';                        // Structured Reference (optional)
                    $qrDataLines[] = $reference;                // Unstructured Remittance (max 140 chars)
                    $qrDataLines[] = '';                        // Beneficiary to Originator Information

                    $qrData = implode("\n", $qrDataLines);

                    // Erstelle QR-Code - die Größe wird später beim Rendern im HTML angepasst
                    $qrCode = new QrCode($qrData);
                    $writer = new PngWriter();
                    $result = $writer->write($qrCode);

                    // Konvertiere zu Base64 für Inline-Anzeige
                    $qrCodeBase64 = base64_encode($result->getString());
                @endphp
                <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR-Code" style="max-width: 75px; max-height: 75px;">
                <div style="font-size: 6pt; margin-top: 5px;">QR-Code für Überweisung</div>
            </div>
        </div>
    </div>

</div>

<div class="footer">
    <!-- Erste Zeile: Rechnungsnummer mittig mit Seitenangabe -->
    <div style="text-align: center; margin-bottom: 5px;">
        Rechnungsnummer: {{ $record->invoice_number }} / Seite <span class="page-number"></span>
    </div>

    <!-- Zeile 2: Firmeninfo -->
    <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
        {{ $settings->company_name }}
        @if($settings->company_address) | {{ $settings->company_address }}@endif
        @if($settings->phone) | {{ $settings->phone }}@endif
        @if($settings->email) | {{ $settings->email }}@endif
    </div>

    <!-- Zeile 3: Amtsgericht und Geschäftsführer -->
    <div style="text-align: center; margin-bottom: 2px; font-size: 6pt; color: #2563eb;">
        @if($settings->commercial_register && $settings->commercial_register_number)
            {{ $settings->commercial_register }} {{ $settings->commercial_register_number }}
        @endif
        @if($settings->commercial_register && $settings->commercial_register_number && $settings->vat_id) | @endif
        @if($settings->vat_id)
            USt-IdNr.: {{ $settings->vat_id }}
        @endif
        @if(($settings->commercial_register && $settings->commercial_register_number) || $settings->vat_id)
            @if($settings->management) | @endif
        @endif
        @if($settings->management)
            Geschäftsführung: {{ $settings->management }}
        @endif
    </div>

    <!-- Footer-Inhalte (3 Spalten) -->
    <div class="footer-content">
        <div class="footer-section">
            &nbsp;
        </div>
        <div class="footer-section text-center">
            @if($settings->tax_number)
            Steuernr.: {{ $settings->tax_number }}
            @endif
        </div>
        <div class="footer-section text-right">
            &nbsp;
        </div>
    </div>
</div>
</body>
</html>