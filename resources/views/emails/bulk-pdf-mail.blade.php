<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solar-Abrechnungen PDF-Export</title>
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
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border: 1px solid #e9ecef; max-width: 600px;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #f39c12; padding: 30px; text-align: center; border-bottom: 1px solid #e9ecef;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: white; font-family: Arial, Helvetica, sans-serif;">☀️ Solar-Abrechnungen PDF-Export</h1>
                            <div style="margin-top: 5px; font-size: 14px; color: white; font-family: Arial, Helvetica, sans-serif;">{{ $companyName }}</div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333;">
                            
                            <!-- Begrüßungstext -->
                            <p style="margin: 0 0 15px 0; font-size: 14px; font-family: Arial, Helvetica, sans-serif;">Hallo,</p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 14px; font-family: Arial, Helvetica, sans-serif;">anbei erhalten Sie die angeforderten Solar-Abrechnungen als PDF-Dateien.</p>

                            <!-- Export-Details Box -->
                            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #f39c12;">
                                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px; font-family: Arial, Helvetica, sans-serif;">📊 Export-Details</h3>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="font-size: 14px;">
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; width: 40%; font-family: Arial, Helvetica, sans-serif;">Anzahl Dateien:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">{{ $totalCount }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; color: #666; font-family: Arial, Helvetica, sans-serif;">Export-Datum:</td>
                                        <td style="padding: 5px 0; font-weight: 600; color: #333; font-family: Arial, Helvetica, sans-serif;">{{ now()->format('d.m.Y H:i') }} Uhr</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Hinweistext -->
                            <p style="color: #666; font-size: 14px; margin: 20px 0; font-family: Arial, Helvetica, sans-serif;">
                                Alle PDF-Dateien sind als Anhänge beigefügt. Bitte prüfen Sie die Vollständigkeit der Dateien.
                            </p>

                            <!-- Timestamp -->
                            <div style="font-size: 12px; color: #999; margin-top: 20px; text-align: center; font-family: Arial, Helvetica, sans-serif;">
                                Automatisch generiert: {{ now()->format('d.m.Y H:i:s') }} Uhr
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef; font-size: 12px; color: #666; font-family: Arial, Helvetica, sans-serif;">
                            <div style="line-height: 1.4;">
                                <strong style="font-size: 14px;">{{ $companyName }}</strong><br>
                                @if($settings->company_address)
                                {{ $settings->company_address }}<br>
                                @endif
                                @if($settings->company_postal_code || $settings->company_city)
                                {{ $settings->company_postal_code }} {{ $settings->company_city }}<br>
                                @endif
                                @if($settings->email)
                                <a href="mailto:{{ $settings->email }}" style="color: #f39c12; text-decoration: none;">{{ $settings->email }}</a><br>
                                @endif
                                @if($settings->website)
                                <a href="{{ $settings->website }}" target="_blank" style="color: #f39c12; text-decoration: none;">{{ $settings->website }}</a>
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
