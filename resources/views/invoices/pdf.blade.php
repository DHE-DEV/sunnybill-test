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
            margin: {{ $settings->pdf_margins }};
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
            margin-left: 30px;
            width: 60%;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2563eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        }
        
        .totals th, .totals td {
            border: none;
            padding: 5px 10px;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333 !important;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #2563eb;
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
            <div>{{ $settings->company_address }}</div>
            <div>{{ $settings->company_postal_code }} {{ $settings->company_city }}</div>
            @if($settings->phone)
                <div>Tel: {{ $settings->phone }}</div>
            @endif
            @if($settings->email)
                <div>E-Mail: {{ $settings->email }}</div>
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
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong>Kundennummer:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;">{{ $record->customer->customer_number ?? 'N/A' }}</td>
                </tr>
                @if($record->customer->email)
                <tr>
                    <td style="border: none; padding: 2px 10px 2px 0; text-align: left; white-space: nowrap;"><strong>E-Mail:</strong></td>
                    <td style="border: none; padding: 2px 0; text-align: right; white-space: nowrap;">{{ $record->customer->email }}</td>
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
                <th class="text-center">MwSt.</th>
                <th class="text-right">Gesamtpreis</th>
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
                <td class="text-right">{{ $item->formatted_unit_price }}</td>
                <td class="text-center">{{ number_format($item->tax_rate * 100, 0) }}%</td>
                <td class="text-right">{{ $item->formatted_total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th>Nettobetrag:</th>
                <td class="text-right">{{ PriceFormatter::formatTotalPrice($record->net_amount) }}</td>
            </tr>
            <tr>
                <th>MwSt. ({{ $record->items->count() > 0 ? number_format($record->items->first()->tax_rate * 100, 0) : '19' }}%):</th>
                <td class="text-right">{{ PriceFormatter::formatTotalPrice($record->tax_amount) }}</td>
            </tr>
            <tr class="total-row">
                <th>Gesamtbetrag:</th>
                <td class="text-right">{{ PriceFormatter::formatTotalPrice($record->total) }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-info">
        <strong>Zahlungsinformationen:</strong><br>
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

</div>

<div class="footer">
    <div class="footer-content text-center">
        <div class="footer-left">
            <strong>{{ $settings->company_name }}</strong> • {{ $settings->company_address }} • {{ $settings->company_postal_code }} {{ $settings->company_city }}
            @if($settings->phone) • Tel: {{ $settings->phone }}@endif
            @if($settings->email) • E-Mail: {{ $settings->email }}@endif
        </div>
        
        <div class="footer-center">
            @if($settings->management)
                Geschäftsführung: {{ $settings->management }}
            @endif
            @if($settings->commercial_register && $settings->commercial_register_number)
                @if($settings->management) • @endif{{ $settings->commercial_register }} {{ $settings->commercial_register_number }}
            @endif
            <br>
            @if($settings->iban)
                IBAN: {{ $settings->iban }}
            @endif
            @if($settings->bic)
                @if($settings->iban) • @endif BIC: {{ $settings->bic }}
            @endif
            @if($settings->tax_number)
                @if($settings->iban || $settings->bic) • @endif USt-IdNr.: {{ $settings->tax_number }}
            @endif
        </div>
    </div>
</div>
</body>
</html>