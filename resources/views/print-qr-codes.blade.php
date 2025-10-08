<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR-Codes drucken</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }

        @media print {
            @page {
                margin-top: 0;
                margin-bottom: 0;
            }
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

        .qr-page {
            page-break-after: always;
            padding: 1cm;
        }

        .qr-page:last-child {
            page-break-after: auto;
        }

        .print-header {
            text-align: center;
            padding-bottom: 2rem;
        }

        .print-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }

        .print-header .subtitle {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }

        .billing-info {
            background: #f8fafc;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
            background: #f8fafc;
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

        .transfer-details {
            flex: 1;
        }

        .detail-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            border-radius: 6px;
            margin-top: 0.5rem;
        }

        .instructions {
            margin-top: 2rem;
        }

        .instructions h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .instructions p {
            margin-bottom: 0.5rem;
        }

        .instructions ul {
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
        }

        @media screen {
            body {
                background: #f3f4f6;
            }

            .qr-page {
                max-width: 21cm;
                margin: 20px auto;
                background: white;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    @if(count($qrCodeData) > 0)
        @foreach($qrCodeData as $index => $qrData)
            <div class="qr-page">
                <div class="print-header">
                    <h1>Banking-App QR-Code</h1>
                    <div class="subtitle">Einfach scannen und bezahlen...</div>
                </div>

                <div class="billing-info">
                    <h2>Abrechnungsdetails</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Abrechnungsnummer</span>
                            <span class="info-value">{{ $qrData['solarPlantBilling']->invoice_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Abrechnungsdatum / Periode</span>
                            <span class="info-value">{{ $qrData['solarPlantBilling']->created_at?->format('d.m.Y') }} / {{ $qrData['solarPlantBilling']->billing_month }}-{{ $qrData['solarPlantBilling']->billing_year }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Kunde / Kundennummer</span>
                            <span class="info-value">{{ $qrData['customer']->name }} / {{ $qrData['customer']->customer_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Solaranlage</span>
                            <span class="info-value">{{ $qrData['solarPlantBilling']->solarPlant?->name }}</span>
                        </div>
                    </div>
                </div>

                <div class="qr-section">
                    <div class="qr-code-container">
                        <img src="data:image/png;base64,{{ $qrData['qrCodeData']['qrCode'] }}" alt="QR-Code für Banking">
                    </div>

                    <div class="transfer-details">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Empfänger</span>
                                <span class="info-value">{{ $qrData['qrCodeData']['data']['beneficiaryName'] }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">IBAN</span>
                                <span class="info-value">{{ rtrim(chunk_split($qrData['qrCodeData']['data']['beneficiaryAccount'], 4, ' ')) }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Betrag</span>
                                <span class="info-value">{{ number_format($qrData['qrCodeData']['data']['amount'], 2, ',', '.') }} EUR</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">BIC</span>
                                <span class="info-value">{{ $qrData['qrCodeData']['data']['beneficiaryBIC'] }}</span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="info-item">
                                <span class="info-label">Verwendungszweck</span>
                                <span class="info-value">{{ $qrData['qrCodeData']['data']['remittanceInformation'] }}</span>
                            </div>
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
            </div>
        @endforeach

        <script>
            // Automatisch drucken wenn die Seite geladen wird
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    @else
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;">
            <p style="color: #6b7280; font-size: 1rem;">Keine QR-Codes zum Drucken verfügbar</p>
        </div>
    @endif
</body>
</html>
