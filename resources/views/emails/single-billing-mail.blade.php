<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihre monatlichge Abrechnung</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f8f8a6;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: #f8f9fa;
            color: black;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .company {
            margin-top: 5px;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            #border-left: 4px solid #2563eb;
            padding: 20px;
            #margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .info-box h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stats {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 15px;
            font-size: 14px;
        }
        .stats .label {
            font-weight: 600;
            color: #666;
        }
        .stats .value {
            color: #333;
            font-weight: 600;
        }
        .custom-message {
            #background-color: #e8f4f8;
            #border-left: 4px solid #2563eb;
            #padding: 20px;
            margin: 10px 0;
            border-radius: 0 4px 4px 0;
            white-space: pre-line;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #666;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .timestamp {
            font-size: 12px;
            color: #7a7a7aff;
            margin-top: 30px;
            text-align: left;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content {
                padding: 20px;
            }
            .stats {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ihre monatliche Abrechnung</h1>
        </div>

        <div class="content">
            <div class="custom-message">
            Sehr geehrte Kundinnen und Kunden,<br><br>anbei erhalten Sie die aktuelle Abrechnung für Ihren Anteil an der Solaranlage.<br>
            </div>
            <div class="info-box">
                <h3>Abrechnungs-Details</h3>
                
                <div class="stats">
                    <span class="label">Kunde:</span>
                    <span class="value">
                        {{ $billing->customer->customer_type === 'business' && $billing->customer->company_name 
                            ? $billing->customer->company_name 
                            : $billing->customer->name }}
                    </span>

                    <span class="label">Solaranlage:</span>
                    <span class="value">{{ $billing->solarPlant->name }} ({{ $billing->solarPlant->plant_number }})</span>

                    <span class="label">Leistungszeitraum:</span>
                    <span class="value">{{ sprintf('%02d/%04d', $billing->billing_month, $billing->billing_year) }}</span>

                    @if($billing->invoice_number)
                    <span class="label">Belegnummer:</span>
                    <span class="value">{{ $billing->invoice_number }}</span>
                    @endif

                    @if($billing->net_amount < 0)
                        <span class="label">Gutschrift:</span>
                        <span class="value">{{ number_format(abs($billing->net_amount), 2, ',', '.') }} €</span>
                    @else
                        <span class="label">Gesamtbetrag:</span>
                        <span class="value">{{ number_format($billing->net_amount, 2, ',', '.') }} €</span>
                    @endif

                </div>
            </div>

            <div class="custom-message">
                Die eingespeisten Strommengen wurden durch unseren Direktvermarkter vergütet und die daraus resultierenden Erlöse haben wir anteilig berechnet.<br>Die Details können Sie der beigefügten Abrechnung entnehmen.
                <br>Die Auszahlung Ihres Anteils erfolgt zeitnah auf das von Ihnen hinterlegte Bankkonto. Sollten sich Ihre Kontodaten geändert haben oder Rückfragen zur Abrechnung bestehen, kontaktieren Sie uns bitte vorzugsweise per E-Mail, damit wir Ihr Anliegen effizient bearbeiten können.<br>
                Alternativ erreichen Sie uns auch telefonisch unter 02234 43 00 614. @if($customMessage)<br><br>{{ $customMessage }}@endif

                <br>Mir freundlichen Grüßen,<br>Ihr Prosoltec Anlagenbetreiber-Team
            </div>

            <div class="timestamp">
            Diese E-Mail enthält vertrauliche und/oder rechtlich geschützte Informationen. Wenn Sie nicht der richtige Adressat sind, oder die E-Mail irrtümlich erhalten haben, informieren Sie bitte den Absender und löschen Sie diese Mail. Das unerlaubte Kopieren sowie die unbefugte Weitergabe dieser E-Mail sind nicht gestattet.
            </div>
        </div>

        <div class="footer">
            @if(isset($logoBase64) && $logoBase64)
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="{{ $logoBase64 }}" 
                     alt="{{ $companyName }} Logo" 
                     style="max-height: 120px; max-width: 400px; height: auto; width: auto; border: none;">
            </div>
            @elseif($settings && $settings->hasLogo())
            <!-- Logo konfiguriert aber konnte nicht geladen werden -->
            <div style="text-align: center; margin-bottom: 15px; color: #999; font-size: 11px;">
                [Logo konfiguriert aber konnte nicht geladen werden: {{ $settings->logo_path }}]
            </div>
            @endif
            <p>
                <strong>{{ $companyName }}</strong><br>
                @if($settings->company_address)
                {{ $settings->company_address }}<br>
                @endif
                @if($settings->company_postal_code || $settings->company_city)
                {{ $settings->company_postal_code }} {{ $settings->company_city }}<br>
                @endif
                @if($settings->email)
                <a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a><br>
                @endif
                @if($settings->website)
                <a href="{{ $settings->website }}" target="_blank">{{ $settings->website }}</a>
                @endif
            </p>
        </div>
    </div>
</body>
</html>
