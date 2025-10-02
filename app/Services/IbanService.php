<?php

namespace App\Services;

use Iban\Validation\Validator;
use Iban\Validation\Iban;

class IbanService
{
    protected $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * Validiert eine IBAN und gibt Bank-Informationen zurück
     *
     * @param string $iban
     * @return array
     */
    public function validateAndGetBankInfo(string $iban): array
    {
        // Entferne Leerzeichen und konvertiere zu Großbuchstaben
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Validiere IBAN
        try {
            $isValid = $this->validator->validate($iban);

            if (!$isValid) {
                return [
                    'valid' => false,
                    'error' => 'Ungültige IBAN',
                ];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'IBAN-Validierung fehlgeschlagen: ' . $e->getMessage(),
            ];
        }

        // Erstelle IBAN-Objekt
        $ibanObject = new Iban($iban);

        // Extrahiere Ländercode und Bankleitzahl
        $countryCode = substr($iban, 0, 2);
        $bankCode = $this->extractBankCode($iban, $countryCode);

        // Hole Bank-Informationen
        $bankInfo = $this->getBankInfoByCountryAndBankCode($countryCode, $bankCode, $iban);

        return [
            'valid' => true,
            'iban' => $ibanObject->format(Iban::FORMAT_ELECTRONIC), // Ohne Leerzeichen
            'iban_formatted' => $ibanObject->format(Iban::FORMAT_PRINT), // Mit Leerzeichen
            'country_code' => $countryCode,
            'bank_code' => $bankCode,
            'bank_name' => $bankInfo['bank_name'] ?? null,
            'bic' => $bankInfo['bic'] ?? null,
        ];
    }

    /**
     * Extrahiert die Bankleitzahl aus der IBAN basierend auf dem Ländercode
     *
     * @param string $iban
     * @param string $countryCode
     * @return string|null
     */
    protected function extractBankCode(string $iban, string $countryCode): ?string
    {
        // Bankleitzahl-Position variiert je nach Land
        // Deutschland: Position 4-12 (8 Stellen)
        // Österreich: Position 4-8 (5 Stellen)
        // Schweiz: Position 4-8 (5 Stellen)

        switch ($countryCode) {
            case 'DE':
                return substr($iban, 4, 8);
            case 'AT':
                return substr($iban, 4, 5);
            case 'CH':
                return substr($iban, 4, 5);
            case 'FR':
                return substr($iban, 4, 10);
            case 'IT':
                return substr($iban, 5, 10);
            case 'ES':
                return substr($iban, 4, 8);
            case 'NL':
                return substr($iban, 4, 4);
            case 'BE':
                return substr($iban, 4, 3);
            default:
                return null;
        }
    }

    /**
     * Holt Bank-Informationen basierend auf Ländercode und Bankleitzahl
     * Verwendet eine API oder lokale Datenbank
     *
     * @param string $countryCode
     * @param string|null $bankCode
     * @param string $fullIban
     * @return array
     */
    protected function getBankInfoByCountryAndBankCode(string $countryCode, ?string $bankCode, string $fullIban = ''): array
    {
        if (!$bankCode) {
            return [];
        }

        // Deutsche Banken - Erweiterte Liste
        if ($countryCode === 'DE') {
            $germanBanks = $this->getGermanBankData();

            if (isset($germanBanks[$bankCode])) {
                return $germanBanks[$bankCode];
            }

            // Fallback: Versuche über BIC-API (wenn verfügbar)
            return $this->getBankInfoFromApi($fullIban);
        }

        // Für andere Länder: API-Lookup
        return $this->getBankInfoFromApi($fullIban);
    }

    /**
     * Deutsche Bankdaten (häufigste Banken)
     * Erweiterte Liste mit BIC
     *
     * @return array
     */
    protected function getGermanBankData(): array
    {
        return [
            // Großbanken
            '37040044' => ['bank_name' => 'Commerzbank', 'bic' => 'COBADEFFXXX'],
            '20070000' => ['bank_name' => 'Deutsche Bank', 'bic' => 'DEUTDEFFXXX'],
            '50010517' => ['bank_name' => 'ING-DiBa', 'bic' => 'INGDDEFFXXX'],
            '10090000' => ['bank_name' => 'Berliner Volksbank', 'bic' => 'BEVODEBB'],
            '76020070' => ['bank_name' => 'Hypovereinsbank', 'bic' => 'HYVEDEMM'],

            // Sparkassen
            '50050000' => ['bank_name' => 'Frankfurter Sparkasse', 'bic' => 'HELADEF1822'],
            '10050000' => ['bank_name' => 'Berliner Sparkasse', 'bic' => 'BELADEBEXXX'],
            '20050550' => ['bank_name' => 'Hamburger Sparkasse', 'bic' => 'HASPDEHHXXX'],
            '70150000' => ['bank_name' => 'Stadtsparkasse München', 'bic' => 'SSKMDEMMXXX'],
            '60050101' => ['bank_name' => 'Kreissparkasse Köln', 'bic' => 'COLSDE33XXX'],

            // Genossenschaftsbanken
            '50060400' => ['bank_name' => 'DZ Bank', 'bic' => 'GENODEFF'],
            '70090500' => ['bank_name' => 'Sparda-Bank München', 'bic' => 'GENODEF1S04'],
            '50090500' => ['bank_name' => 'Sparda-Bank Hessen', 'bic' => 'GENODEF1S12'],

            // Postbank
            '37010050' => ['bank_name' => 'Postbank Köln', 'bic' => 'PBNKDEFFXXX'],
            '10010010' => ['bank_name' => 'Postbank Berlin', 'bic' => 'PBNKDEFFXXX'],
            '20010020' => ['bank_name' => 'Postbank Hamburg', 'bic' => 'PBNKDEFFXXX'],

            // Online-Banken
            '51210800' => ['bank_name' => 'N26 Bank', 'bic' => 'NTSBDEB1XXX'],
            '76026000' => ['bank_name' => 'Consorsbank', 'bic' => 'CSDBDE71XXX'],
            '30120400' => ['bank_name' => 'comdirect Bank', 'bic' => 'COBADEFFXXX'],

            // Weitere wichtige Banken
            '50050201' => ['bank_name' => 'Nassauische Sparkasse', 'bic' => 'NASSDE55XXX'],
            '60020030' => ['bank_name' => 'Baden-Württembergische Bank', 'bic' => 'SOLADEST600'],
            '26580070' => ['bank_name' => 'Landessparkasse zu Oldenburg', 'bic' => 'BRLADE21LZO'],
        ];
    }

    /**
     * Holt Bank-Informationen von einer externen API
     * (Fallback wenn keine lokalen Daten vorhanden)
     *
     * @param string $iban Vollständige IBAN
     * @return array
     */
    protected function getBankInfoFromApi(string $iban): array
    {
        try {
            // Verwende OpenIBAN API (kostenlos, keine Registrierung nötig)
            $url = "https://openiban.com/validate/{$iban}?getBIC=true&validateBankCode=true";

            $response = \Http::timeout(5)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['bankData'])) {
                    return [
                        'bank_name' => $data['bankData']['name'] ?? null,
                        'bic' => $data['bankData']['bic'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('IBAN API lookup failed', [
                'iban' => substr($iban, 0, 8) . '***', // Nur ersten Teil loggen (Datenschutz)
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }
}
