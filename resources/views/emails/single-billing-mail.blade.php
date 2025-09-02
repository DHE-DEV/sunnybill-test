<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihre monatliche Abrechnung</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f8f8f8; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f8f8;">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border: 1px solid #e9ecef; max-width: 600px;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-bottom: 1px solid #e9ecef;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">Ihre monatliche Abrechnung</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333;">
                            
                            <!-- Einleitungstext -->
                            <div style="margin-bottom: 25px; font-size: 14px;">
                                Sehr geehrte Kundinnen und Kunden,<br><br>
                                anbei erhalten Sie die aktuelle Abrechnung für Ihren Anteil an der Solaranlage.
                            </div>

                            <!-- Abrechnungs-Details Box -->
                            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #2563eb;">
                                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px; font-family: Arial, Helvetica, sans-serif;">Abrechnungs-Details</h3>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="font-size: 14px;">
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; width: 40%; font-family: Arial, Helvetica, sans-serif;">Kunde:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">
                                            {{ $billing->customer->customer_type === 'business' && $billing->customer->company_name 
                                                ? $billing->customer->company_name 
                                                : $billing->customer->name }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; font-family: Arial, Helvetica, sans-serif;">Solaranlage:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">{{ $billing->solarPlant->name }} ({{ $billing->solarPlant->plant_number }})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; font-family: Arial, Helvetica, sans-serif;">Leistungszeitraum:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">{{ sprintf('%02d/%04d', $billing->billing_month, $billing->billing_year) }}</td>
                                    </tr>
                                    @if($billing->invoice_number)
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; font-family: Arial, Helvetica, sans-serif;">Belegnummer:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">{{ $billing->invoice_number }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; font-family: Arial, Helvetica, sans-serif;">
                                            @if($billing->net_amount < 0)
                                                Gutschrift:
                                            @else
                                                Gesamtbetrag:
                                            @endif
                                        </td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">
                                            @if($billing->net_amount < 0)
                                                {{ number_format(abs($billing->net_amount), 2, ',', '.') }} €
                                            @else
                                                {{ number_format($billing->net_amount, 2, ',', '.') }} €
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Haupttext -->
                            <div style="margin: 20px 0; font-size: 14px; line-height: 1.6;">
                                Die eingespeisten Strommengen wurden durch unseren Direktvermarkter vergütet und die daraus resultierenden Erlöse haben wir anteilig berechnet.<br>
                                Die Details können Sie der beigefügten Abrechnung entnehmen.<br><br>
                                Die Auszahlung Ihres Anteils erfolgt zeitnah auf das von Ihnen hinterlegte Bankkonto. Sollten sich Ihre Kontodaten geändert haben oder Rückfragen zur Abrechnung bestehen, kontaktieren Sie uns bitte vorzugsweise per E-Mail, damit wir Ihr Anliegen effizient bearbeiten können.<br><br>
                                Alternativ erreichen Sie uns auch telefonisch unter 02234 43 00 614.
                                @if($customMessage)<br><br>{{ $customMessage }}@endif
                                <br><br>
                                Mit freundlichen Grüßen,<br>
                                Ihr Prosoltec Anlagenbetreiber-Team
                            </div>

                            <!-- Disclaimer -->
                            <div style="font-size: 12px; color: #7a7a7a; margin-top: 30px; text-align: left; line-height: 1.4;">
                                Diese E-Mail enthält vertrauliche und/oder rechtlich geschützte Informationen. Wenn Sie nicht der richtige Adressat sind, oder die E-Mail irrtümlich erhalten haben, informieren Sie bitte den Absender und löschen Sie diese Mail. Das unerlaubte Kopieren sowie die unbefugte Weitergabe dieser E-Mail sind nicht gestattet.
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef; font-size: 12px; color: #666; font-family: Arial, Helvetica, sans-serif;">
                            @if(isset($logoBase64) && $logoBase64)
                            <div style="text-align: center; margin-bottom: 15px;">
                                <img src="{{ $logoBase64 }}" alt="{{ $companyName }} Logo" style="max-height: 60px; max-width: 200px; height: auto; width: auto; border: none; display: block; margin: 0 auto;">
                            </div>
                            @elseif($settings && $settings->hasLogo())
                            <div style="text-align: center; margin-bottom: 15px; color: #999; font-size: 11px;">
                                [Logo konfiguriert aber konnte nicht geladen werden: {{ $settings->logo_path }}]
                            </div>
                            @endif
                            
                            <div style="line-height: 1.4;">
                                <strong style="font-size: 14px;">{{ $companyName }}</strong><br>
                                @if($settings->company_address)
                                {{ $settings->company_address }}<br>
                                @endif
                                @if($settings->company_postal_code || $settings->company_city)
                                {{ $settings->company_postal_code }} {{ $settings->company_city }}<br>
                                @endif
                                @if($settings->email)
                                <a href="mailto:{{ $settings->email }}" style="color: #2563eb; text-decoration: none;">{{ $settings->email }}</a><br>
                                @endif
                                @if($settings->website)
                                <a href="{{ $settings->website }}" target="_blank" style="color: #2563eb; text-decoration: none;">{{ $settings->website }}</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
