<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Benachrichtigung</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .email-info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .email-info h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .email-info .meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .email-info .meta .label {
            font-weight: 600;
            color: #666;
        }
        .email-info .meta .value {
            color: #333;
        }
        .snippet {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            font-style: italic;
            color: #666;
        }
        .badges {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.important {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .badge.attachments {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s ease;
        }
        .action-button:hover {
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
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
            color: #667eea;
            text-decoration: none;
        }
        .icon {
            width: 16px;
            height: 16px;
            display: inline-block;
        }
        .timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content {
                padding: 20px;
            }
            .email-info .meta {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Gmail Benachrichtigung</h1>
            <div class="company">{{ $companyName }}</div>
        </div>

        <div class="content">
            <div class="greeting">
                Hallo {{ $user->name }},
            </div>

            <p>Sie haben eine neue E-Mail in Ihrem Gmail-Postfach erhalten:</p>

            <div class="email-info">
                <h3>
                    📨 {{ $subject }}
                </h3>

                <div class="meta">
                    <span class="label">Von:</span>
                    <span class="value">{{ $sender }}</span>

                    <span class="label">Empfangen:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($receivedAt)->format('d.m.Y H:i') }} Uhr</span>

                    @if($notificationData['gmail_id'] ?? false)
                    <span class="label">Gmail ID:</span>
                    <span class="value">{{ $notificationData['gmail_id'] }}</span>
                    @endif
                </div>

                @if($snippet)
                <div class="snippet">
                    "{{ $snippet }}"
                </div>
                @endif

                <div class="badges">
                    @if($isImportant)
                    <span class="badge important">
                        ⭐ Wichtig
                    </span>
                    @endif

                    @if($hasAttachments)
                    <span class="badge attachments">
                        📎 Anhänge
                    </span>
                    @endif
                </div>
            </div>

            @if($emailUrl)
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $emailUrl }}" class="action-button">
                    E-Mail anzeigen
                </a>
            </div>
            @endif

            <p style="color: #666; font-size: 14px;">
                Diese Benachrichtigung wurde automatisch generiert, weil Sie Gmail-Benachrichtigungen aktiviert haben.
            </p>

            <div class="timestamp">
                Benachrichtigung gesendet: {{ now()->format('d.m.Y H:i:s') }} Uhr
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

            <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                Sie erhalten diese E-Mail, weil Sie Gmail-Benachrichtigungen aktiviert haben.<br>
                Sie können Ihre Benachrichtigungseinstellungen in Ihrem Benutzerprofil ändern.
            </p>
        </div>
    </div>
</body>
</html>
