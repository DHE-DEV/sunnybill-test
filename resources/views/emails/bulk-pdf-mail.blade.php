<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solar-Abrechnungen PDF-Export</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
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
            border-left: 4px solid #f39c12;
            padding: 20px;
            margin: 20px 0;
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
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #666;
        }
        .footer a {
            color: #f39c12;
            text-decoration: none;
        }
        .timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            text-align: center;
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
            <h1>☀️ Solar-Abrechnungen PDF-Export</h1>
            <div class="company">{{ $companyName }}</div>
        </div>

        <div class="content">
            <p>Hallo,</p>

            <p>anbei erhalten Sie die angeforderten Solar-Abrechnungen als PDF-Dateien.</p>

            <div class="info-box">
                <h3>📊 Export-Details</h3>
                
                <div class="stats">
                    <span class="label">Anzahl Dateien:</span>
                    <span class="value">{{ $totalCount }}</span>

                    <span class="label">Export-Datum:</span>
                    <span class="value">{{ now()->format('d.m.Y H:i') }} Uhr</span>
                </div>
            </div>

            <p style="color: #666; font-size: 14px;">
                Alle PDF-Dateien sind als Anhänge beigefügt. Bitte prüfen Sie die Vollständigkeit der Dateien.
            </p>

            <div class="timestamp">
                Automatisch generiert: {{ now()->format('d.m.Y H:i:s') }} Uhr
            </div>
        </div>

        <div class="footer">
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
