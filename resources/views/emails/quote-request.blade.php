<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neue Angebotsanfrage</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg, #1a202c, #2d3748); padding:30px 40px; text-align:center;">
                            <h1 style="color:#ffd700; margin:0; font-size:24px;">Neue Angebotsanfrage</h1>
                            <p style="color:rgba(255,255,255,0.8); margin:8px 0 0; font-size:14px;">VoltMaster Preiskalkulator</p>
                        </td>
                    </tr>

                    {{-- Kontaktdaten --}}
                    <tr>
                        <td style="padding:30px 40px 10px;">
                            <h2 style="color:#1a202c; font-size:18px; margin:0 0 15px; border-bottom:2px solid #f53003; padding-bottom:8px;">Kontaktdaten</h2>
                            <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px; color:#333;">
                                <tr>
                                    <td style="font-weight:bold; width:160px; padding:6px 0;">Firma:</td>
                                    <td style="padding:6px 0;">{{ $data['firma'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Ansprechpartner:</td>
                                    <td style="padding:6px 0;">{{ $data['ansprechpartner'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Adresse:</td>
                                    <td style="padding:6px 0;">{{ $data['strasse'] }}, {{ $data['plz'] }} {{ $data['ort'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Telefon:</td>
                                    <td style="padding:6px 0;">{{ $data['telefon'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">E-Mail:</td>
                                    <td style="padding:6px 0;"><a href="mailto:{{ $data['email'] }}" style="color:#f53003;">{{ $data['email'] }}</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Konfiguration --}}
                    <tr>
                        <td style="padding:20px 40px 10px;">
                            <h2 style="color:#1a202c; font-size:18px; margin:0 0 15px; border-bottom:2px solid #f53003; padding-bottom:8px;">Gewählte Konfiguration</h2>
                            <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px; color:#333;">
                                <tr>
                                    <td style="font-weight:bold; width:200px; padding:6px 0;">Solaranlagen:</td>
                                    <td style="padding:6px 0;">{{ $data['solaranlagen'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Beteiligungen:</td>
                                    <td style="padding:6px 0;">{{ $data['beteiligungen'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Benutzer:</td>
                                    <td style="padding:6px 0;">{{ $data['benutzer'] }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding:6px 0;">Zahlungsweise:</td>
                                    <td style="padding:6px 0;">{{ $data['zahlungsweise'] === 'yearly' ? 'Jährlich' : 'Monatlich' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Module --}}
                    <tr>
                        <td style="padding:20px 40px 10px;">
                            <h2 style="color:#1a202c; font-size:18px; margin:0 0 15px; border-bottom:2px solid #f53003; padding-bottom:8px;">Aktive Module</h2>
                            <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px; color:#333;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        @if(!empty($data['modul_aufgaben']))
                                            <span style="color:#00ba88; font-weight:bold;">&#10003;</span> Aufgaben (99 € / Monat)
                                        @else
                                            <span style="color:#ccc;">&#10007;</span> <span style="color:#999;">Aufgaben</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        @if(!empty($data['modul_projekte']))
                                            <span style="color:#00ba88; font-weight:bold;">&#10003;</span> Projekte (199 € / Monat)
                                        @else
                                            <span style="color:#ccc;">&#10007;</span> <span style="color:#999;">Projekte</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        @if(!empty($data['modul_dokumente']))
                                            <span style="color:#00ba88; font-weight:bold;">&#10003;</span> Dokumente (299 € / Monat, inkl. 1 GB)
                                        @else
                                            <span style="color:#ccc;">&#10007;</span> <span style="color:#999;">Dokumente</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Gesamtpreis --}}
                    <tr>
                        <td style="padding:20px 40px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg, #1a202c, #2d3748); border-radius:10px; overflow:hidden;">
                                <tr>
                                    <td style="padding:20px 25px; text-align:center;">
                                        <p style="color:rgba(255,255,255,0.7); margin:0 0 5px; font-size:13px;">Gesamtpreis (netto)</p>
                                        <p style="color:#ffd700; margin:0; font-size:28px; font-weight:bold;">{{ $data['gesamtpreis'] }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:20px 40px; text-align:center; border-top:1px solid #e2e8f0;">
                            <p style="color:#718096; font-size:12px; margin:0;">Diese Anfrage wurde über den VoltMaster Preiskalkulator gesendet.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
