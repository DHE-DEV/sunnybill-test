<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR-Code Banking - {{ $solarPlantBilling->billing_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: white;
        }

        .print-header {
            text-align: center;
            #margin-bottom: 3rem;
            #border-bottom: 2px solid #e5e5e5;
            padding-bottom: 2rem;
        }

        .print-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            #margin-bottom: 0.5rem;
        }

        .print-header .subtitle {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }

        .print-header .invoice-number {
            font-size: 18px;
            color: #3b82f6;
            font-weight: 600;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #f0f9ff;
            border-radius: 6px;
            display: inline-block;
        }

        .billing-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #3b82f6;
        }

        .billing-info h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
        }

        .qr-section {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            margin: 2rem 0;
            padding: 2rem;
            background: white;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
        }

        .qr-code-container {
            flex-shrink: 0;
            text-align: center;
        }

        .qr-code-container img {
            width: 200px;
            height: 200px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .qr-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 1rem;
        }

        .transfer-details {
            flex: 1;
        }

        .transfer-details h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 0.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            background: #ffffff;
            #padding: 1rem;
            border-radius: 6px;
            margin-top: 0.5rem;
        }

        .detail-label {
            font-weight: 500;
            color: #374151;
        }

        .detail-value {
            font-weight: 600;
            color: #1f2937;
            text-align: right;
        }

        .instructions {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .instructions h4 {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 0.75rem;
        }

        .instructions p {
            color: #92400e;
            margin-bottom: 0.5rem;
        }

        .instructions ul {
            color: #92400e;
            margin-left: 1.5rem;
        }

        .instructions li {
            margin-bottom: 0.25rem;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>Banking-App QR-Code</h1>
        <div class="subtitle"></div>
    </div>
@php dump($qrCodeData); @endphp
@php dump($solarPlantBilling); @endphp
    <div class="billing-info">
        <h2>Abrechnungsdetails</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Abrechnungsnummer</span>
                <span class="info-value">{{ $solarPlantBilling->invoice_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Datum</span>
                <span class="info-value">{{ $solarPlantBilling->created_at?->format('d.m.Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Kunde</span>
                <span class="info-value">{{ $solarPlantBilling->customer?->company_name ?: $solarPlantBilling->customer?->first_name . ' ' . $solarPlantBilling->customer?->last_name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Solaranlage</span>
                <span class="info-value">{{ $solarPlantBilling->solarPlant?->name }}</span>
            </div>
        </div>
    </div>

    <div class="qr-section">
        <div class="qr-code-container">
            <img src="data:image/png;base64,{{ $qrCodeData['qrCode'] }}" alt="QR-Code für Banking">
        </div>

        <div class="transfer-details">
            
            <div class="detail-row">
                <span class="detail-label">Empfänger:</span>
                <span class="detail-value">{{ $qrCodeData['data']['beneficiaryName'] }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">IBAN:</span>
                <span class="detail-value">{{ $qrCodeData['data']['beneficiaryAccount'] }}</span>
            </div>
            
            @if(!empty($qrCodeData['data']['beneficiaryBIC']))
            <div class="detail-row">
                <span class="detail-label">BIC:</span>
                <span class="detail-value">{{ $qrCodeData['data']['beneficiaryBIC'] }}</span>
            </div>
            @endif
            
            <div class="detail-row">
                <span class="detail-label">Betrag:</span>
                <span class="detail-value">{{ number_format($qrCodeData['data']['amount'], 2, ',', '.') }} EUR</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Verwendungszweck:<br>{{ $qrCodeData['data']['remittanceInformation'] }}</span>
            </div>
            
            
        </div>
    </div>

    <div class="instructions">
        <h4>Anweisungen für die QR-Code Zahlung:</h4>
        <p><strong>Mit Banking-App:</strong></p>
        <ul>
            <li>Öffnen Sie Ihre Banking-App</li>
            <li>Wählen Sie "QR-Code scannen" oder "QR-Überweisung"</li>
            <li>Scannen Sie den QR-Code mit der Kamera Ihres Smartphones</li>
            <li>Prüfen Sie die automatisch ausgefüllten Daten und bestätigen Sie die Überweisung</li>
        </ul>
        <p><strong>Alternative:</strong> Verwenden Sie die oben angegebenen Überweisungsdetails für eine manuelle Überweisung.</p>
    </div>

    <script>
        // Automatisch drucken wenn die Seite geladen wird
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
