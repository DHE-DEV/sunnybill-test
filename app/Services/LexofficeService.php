<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Article;
use App\Models\Invoice;
use App\Models\LexofficeLog;
use App\Models\CompanySetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class LexofficeService
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl = 'https://api.lexoffice.io/v1/';

    public function __construct()
    {
        // Versuche Einstellungen aus CompanySetting zu laden
        $companySetting = CompanySetting::current();
        
        if ($companySetting->hasValidLexwareConfig()) {
            // Verwende Einstellungen aus der Datenbank
            $this->apiKey = $companySetting->getLexwareApiKey();
            $this->baseUrl = rtrim($companySetting->getLexwareApiUrl(), '/') . '/';
        } else {
            // Fallback auf Config-Datei
            $this->apiKey = config('services.lexoffice.api_key');
            $this->baseUrl = config('services.lexoffice.base_url', 'https://api.lexoffice.io/v1/');
        }
        
        if (empty($this->apiKey)) {
            throw new \Exception('Lexware API Key ist nicht konfiguriert. Bitte prüfen Sie die Firmeneinstellungen.');
        }
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Kunden von Lexoffice importieren
     */
    public function importCustomers(): array
    {
        try {
            $response = $this->client->get('contacts', [
                'query' => [
                    'customer' => true,
                    'size' => 100
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $imported = 0;
            $errors = [];

            foreach ($data['content'] ?? [] as $lexofficeCustomer) {
                try {
                    $this->createOrUpdateCustomer($lexofficeCustomer);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Kunde {$lexofficeCustomer['id']}: " . $e->getMessage();
                }
            }

            $this->logAction('customer', 'import', null, null, [
                'imported' => $imported,
                'errors' => $errors
            ], 'success');

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors
            ];

        } catch (RequestException $e) {
            $this->logAction('customer', 'import', null, null, null, 'error', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Artikel von Lexoffice importieren
     */
    public function importArticles(): array
    {
        try {
            $response = $this->client->get('articles');
            $data = json_decode($response->getBody()->getContents(), true);
            $imported = 0;
            $errors = [];

            foreach ($data['content'] ?? [] as $lexofficeArticle) {
                try {
                    $this->createOrUpdateArticle($lexofficeArticle);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Artikel {$lexofficeArticle['id']}: " . $e->getMessage();
                }
            }

            $this->logAction('article', 'import', null, null, [
                'imported' => $imported,
                'errors' => $errors
            ], 'success');

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors
            ];

        } catch (RequestException $e) {
            $this->logAction('article', 'import', null, null, null, 'error', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rechnung zu Lexoffice exportieren
     */
    public function exportInvoice(Invoice $invoice): array
    {
        try {
            $invoiceData = $this->prepareInvoiceData($invoice);
            
            $response = $this->client->post('invoices', [
                'json' => $invoiceData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Lexoffice ID in der Rechnung speichern
            $invoice->update(['lexoffice_id' => $responseData['id']]);

            $this->logAction('invoice', 'export', $invoice->id, $responseData['id'], $invoiceData, 'success');

            return [
                'success' => true,
                'lexoffice_id' => $responseData['id'],
                'data' => $responseData
            ];

        } catch (RequestException $e) {
            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('invoice', 'export', $invoice->id, null, null, 'error', $errorMessage);
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Artikel zu Lexoffice exportieren
     */
    public function exportArticle(Article $article): array
    {
        try {
            $articleData = $this->prepareArticleData($article);
            
            if ($article->lexoffice_id) {
                // Zuerst aktuellen Artikel abrufen, um Version zu erhalten
                $currentResponse = $this->client->get("articles/{$article->lexoffice_id}");
                $currentData = json_decode($currentResponse->getBody()->getContents(), true);
                
                // Version zur Update-Anfrage hinzufügen
                $articleData['version'] = $currentData['version'];
                
                // Artikel aktualisieren
                $response = $this->client->put("articles/{$article->lexoffice_id}", [
                    'json' => $articleData
                ]);
                $action = 'update';
            } else {
                // Neuen Artikel erstellen
                $response = $this->client->post('articles', [
                    'json' => $articleData
                ]);
                $action = 'create';
            }

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Lexoffice ID in dem Artikel speichern
            $article->update(['lexoffice_id' => $responseData['id']]);

            $this->logAction('article', 'export', $article->id, $responseData['id'], $articleData, 'success', null, $responseData);

            return [
                'success' => true,
                'action' => $action,
                'lexoffice_id' => $responseData['id'],
                'data' => $responseData
            ];

        } catch (RequestException $e) {
            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('article', 'export', $article->id, null, $articleData ?? null, 'error', $errorMessage);
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Kunde bidirektional synchronisieren (intelligente Synchronisation)
     */
    public function syncCustomer(Customer $customer): array
    {
        try {
            if (!$customer->lexoffice_id) {
                // Kein Lexoffice-Kunde vorhanden -> Export zu Lexoffice
                return $this->exportCustomerToLexoffice($customer);
            }

            // Lexoffice-Daten abrufen
            try {
                $lexofficeResponse = $this->client->get("contacts/{$customer->lexoffice_id}");
                $lexofficeData = json_decode($lexofficeResponse->getBody()->getContents(), true);
            } catch (RequestException $e) {
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                    // Kunde in Lexoffice nicht mehr vorhanden -> Neuen erstellen
                    $customer->update(['lexoffice_id' => null]);
                    return $this->exportCustomerToLexoffice($customer);
                }
                throw $e;
            }

            // Zeitstempel vergleichen um zu entscheiden, welche Version neuer ist
            $lexofficeUpdated = isset($lexofficeData['updatedDate']) ? 
                \Carbon\Carbon::parse($lexofficeData['updatedDate']) : null;
            
            $localUpdated = $customer->updated_at;
            $lastSynced = $customer->lexoffice_synced_at;

            // Prüfe ob lokale Änderungen (inkl. Adressen) vorliegen
            $hasLocalChanges = $this->hasLocalChanges($customer, $lastSynced);

            // Entscheidungslogik für Synchronisationsrichtung
            $syncDirection = $this->determineSyncDirectionWithAddresses($customer, $lexofficeUpdated, $lastSynced, $hasLocalChanges);

            switch ($syncDirection) {
                case 'export':
                    // Lokale Daten sind neuer -> Export zu Lexoffice
                    return $this->exportCustomerToLexoffice($customer, $lexofficeData['version']);

                case 'import':
                    // Lexoffice-Daten sind neuer -> Import von Lexoffice
                    return $this->importCustomerFromLexoffice($customer, $lexofficeData);

                case 'conflict':
                    // Beide Seiten wurden seit letzter Sync geändert -> Benutzer entscheiden lassen
                    return [
                        'success' => false,
                        'error' => 'Synchronisationskonflikt',
                        'conflict' => true,
                        'local_updated' => $localUpdated->format('d.m.Y H:i:s'),
                        'lexoffice_updated' => $lexofficeUpdated ? $lexofficeUpdated->format('d.m.Y H:i:s') : 'Unbekannt',
                        'last_synced' => $lastSynced ? $lastSynced->format('d.m.Y H:i:s') : 'Nie'
                    ];

                case 'up_to_date':
                default:
                    // Beide Versionen sind aktuell, aber prüfe ob Adressen fehlen
                    $addressesImported = 0;
                    
                    // Prüfe ob lokale Adressen fehlen, aber Lexoffice-Adressen vorhanden sind
                    $hasLocalBilling = $customer->addresses()->where('type', 'billing')->exists();
                    $hasLocalShipping = $customer->addresses()->where('type', 'shipping')->exists();
                    
                    $hasLexofficeBilling = isset($lexofficeData['addresses']['billing']) && !empty($lexofficeData['addresses']['billing']);
                    $hasLexofficeShipping = isset($lexofficeData['addresses']['shipping']) && !empty($lexofficeData['addresses']['shipping']);
                    
                    // Importiere fehlende Adressen
                    if ((!$hasLocalBilling && $hasLexofficeBilling) || (!$hasLocalShipping && $hasLexofficeShipping)) {
                        $addressesImported = $this->importCustomerAddressesFromLexoffice($customer, $lexofficeData['addresses'] ?? []);
                    }
                    
                    $message = 'Kunde ist bereits synchronisiert';
                    if ($addressesImported > 0) {
                        $message .= " ({$addressesImported} fehlende Adresse(n) nachträglich importiert)";
                    }
                    
                    return [
                        'success' => true,
                        'action' => 'no_change',
                        'message' => $message,
                        'addresses_imported' => $addressesImported
                    ];
            }

        } catch (RequestException $e) {
            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('customer', 'sync', $customer->id, null, null, 'error', $errorMessage);
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Bestimme Synchronisationsrichtung basierend auf Zeitstempeln
     */
    private function determineSyncDirection(
        \Carbon\Carbon $localUpdated,
        ?\Carbon\Carbon $lexofficeUpdated,
        ?\Carbon\Carbon $lastSynced
    ): string {
        // Wenn noch nie synchronisiert wurde
        if (!$lastSynced) {
            if (!$lexofficeUpdated) {
                return 'export'; // Nur lokale Daten vorhanden
            }
            // Beide haben Daten, neuere Version gewinnt
            return $localUpdated->gt($lexofficeUpdated) ? 'export' : 'import';
        }

        // Prüfe ob lokale Daten seit letzter Sync geändert wurden (inkl. Adressen)
        $localChanged = $localUpdated->gt($lastSynced);
        
        // Prüfe ob Lexoffice-Daten seit letzter Sync geändert wurden
        $lexofficeChanged = $lexofficeUpdated && $lexofficeUpdated->gt($lastSynced);

        if ($localChanged && $lexofficeChanged) {
            // Beide Seiten geändert -> Konflikt
            return 'conflict';
        } elseif ($localChanged) {
            // Nur lokal geändert -> Export
            return 'export';
        } elseif ($lexofficeChanged) {
            // Nur Lexoffice geändert -> Import
            return 'import';
        } else {
            // Keine Änderungen seit letzter Sync
            return 'up_to_date';
        }
    }

    /**
     * Prüfe ob lokale Daten (inkl. Adressen) seit letzter Sync geändert wurden
     */
    private function hasLocalChanges(Customer $customer, ?\Carbon\Carbon $lastSynced): bool
    {
        if (!$lastSynced) {
            return true; // Noch nie synchronisiert
        }

        // Prüfe Hauptdaten des Kunden
        if ($customer->updated_at->gt($lastSynced)) {
            return true;
        }

        // Prüfe Adressen
        $addressesChanged = $customer->addresses()
            ->where('updated_at', '>', $lastSynced)
            ->exists();

        return $addressesChanged;
    }

    /**
     * Bestimme Synchronisationsrichtung mit Berücksichtigung von Adressänderungen
     */
    private function determineSyncDirectionWithAddresses(
        Customer $customer,
        ?\Carbon\Carbon $lexofficeUpdated,
        ?\Carbon\Carbon $lastSynced,
        bool $hasLocalChanges
    ): string {
        // Wenn noch nie synchronisiert wurde
        if (!$lastSynced) {
            if (!$lexofficeUpdated) {
                return 'export'; // Nur lokale Daten vorhanden
            }
            // Beide haben Daten, lokale Änderungen haben Vorrang wenn vorhanden
            return $hasLocalChanges ? 'export' : 'import';
        }

        // Prüfe ob Lexoffice-Daten seit letzter Sync geändert wurden
        $lexofficeChanged = $lexofficeUpdated && $lexofficeUpdated->gt($lastSynced);

        if ($hasLocalChanges && $lexofficeChanged) {
            // Beide Seiten geändert -> Konflikt
            return 'conflict';
        } elseif ($hasLocalChanges) {
            // Nur lokal geändert (inkl. Adressen) -> Export
            return 'export';
        } elseif ($lexofficeChanged) {
            // Nur Lexoffice geändert -> Import
            return 'import';
        } else {
            // Keine Änderungen seit letzter Sync
            return 'up_to_date';
        }
    }

    /**
     * Kunde zu Lexoffice exportieren (Legacy-Methode für Rückwärtskompatibilität)
     */
    public function exportCustomer(Customer $customer): array
    {
        return $this->syncCustomer($customer);
    }

    /**
     * Kunde zu Lexoffice exportieren (interne Methode)
     */
    private function exportCustomerToLexoffice(Customer $customer, ?int $version = null): array
    {
        $startTime = microtime(true);
        
        try {
            if ($customer->lexoffice_id) {
                // Für Updates: Aktuelle Daten von Lexoffice abrufen
                $versionInfo = $this->getCurrentContactVersion($customer->lexoffice_id);
                
                if (!$versionInfo) {
                    $this->logVersionOperation('fetch_failed', $customer, null, null, 1, 1, 'Konnte aktuelle Version nicht abrufen');
                    return ['success' => false, 'error' => 'Konnte aktuelle Version nicht abrufen'];
                }
                
                // Kundendaten mit allen erforderlichen Feldern für PUT vorbereiten
                $customerData = $this->prepareCustomerDataForUpdate($customer, $versionInfo['data']);
                
                $this->logVersionOperation('update_attempt', $customer, $versionInfo['version'], $versionInfo['version'], 1, 1);
                
                // Log Request-Daten
                Log::info('Lexoffice PUT Request', [
                    'url' => "PUT contacts/{$customer->lexoffice_id}",
                    'request_data' => $customerData,
                    'version_info' => [
                        'current_version' => $versionInfo['version'],
                        'last_updated' => $versionInfo['updatedDate']
                    ]
                ]);
                
                $response = $this->client->put("contacts/{$customer->lexoffice_id}", [
                    'json' => $customerData
                ]);
                $action = 'export_update';
            } else {
                // Neuen Kunde erstellen
                $customerData = $this->prepareCustomerData($customer);
                
                Log::info('Lexoffice POST Request', [
                    'url' => "POST contacts",
                    'request_data' => $customerData
                ]);
                
                $response = $this->client->post('contacts', [
                    'json' => $customerData
                ]);
                $action = 'export_create';
            }

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log Response-Daten
            Log::info('Lexoffice Response', [
                'status' => $response->getStatusCode(),
                'response_data' => $responseData,
                'duration_ms' => $duration
            ]);
            
            // Performance-Logging
            $this->logPerformanceMetrics('customer_export', [
                'duration_ms' => $duration,
                'action' => $action,
                'status_code' => $response->getStatusCode(),
                'data_size_bytes' => strlen($responseBody)
            ]);
            
            // Lexoffice ID und Synchronisationszeitpunkt speichern
            $customer->update([
                'lexoffice_id' => $responseData['id'],
                'lexoffice_synced_at' => now()
            ]);

            // Erfolgreiche Version-Operation loggen
            if ($customer->lexoffice_id && isset($versionInfo)) {
                $this->logVersionOperation('update_success', $customer, $versionInfo['version'], $responseData['version'] ?? null, 1, 1);
            }

            $this->logAction('customer', 'export', $customer->id, $responseData['id'], $customerData, 'success', null, $responseData, [
                'action_type' => $action,
                'duration_ms' => $duration
            ]);

            return [
                'success' => true,
                'action' => $action,
                'lexoffice_id' => $responseData['id'],
                'message' => 'Kunde erfolgreich zu Lexoffice exportiert',
                'data' => $responseData,
                'duration_ms' => $duration
            ];

        } catch (RequestException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log Error-Daten
            $errorData = [
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'No Response',
                'error_message' => $e->getMessage(),
                'duration_ms' => $duration
            ];
            
            if ($e->hasResponse()) {
                $errorData['error_response'] = $e->getResponse()->getBody()->getContents();
            }
            
            Log::error('Lexoffice Error', $errorData);
            
            // Version-Fehler loggen
            if ($customer->lexoffice_id && isset($versionInfo)) {
                $this->logVersionOperation('update_failed', $customer, $versionInfo['version'], null, 1, 1, $e->getMessage());
            }
            
            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('customer', 'export', $customer->id, null, $customerData ?? null, 'error', $errorMessage, null, [
                'duration_ms' => $duration,
                'http_status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'duration_ms' => $duration
            ];
        }
    }

    /**
     * Kunde von Lexoffice importieren (interne Methode)
     */
    private function importCustomerFromLexoffice(Customer $customer, array $lexofficeData): array
    {
        try {
            // Lexoffice-Daten in lokale Struktur konvertieren
            $customerType = 'private';
            $name = '';
            $companyName = null;
            
            if (isset($lexofficeData['company']['name'])) {
                $customerType = 'business';
                $name = $lexofficeData['company']['name'];
                $companyName = $lexofficeData['company']['name'];
            } elseif (isset($lexofficeData['person'])) {
                $customerType = 'private';
                $firstName = $lexofficeData['person']['firstName'] ?? '';
                $lastName = $lexofficeData['person']['lastName'] ?? '';
                $name = trim($firstName . ' ' . $lastName);
            }

            $updateData = [
                'name' => $name ?: $customer->name, // Fallback auf aktuellen Namen
                'customer_type' => $customerType,
                'company_name' => $companyName,
                'email' => isset($lexofficeData['emailAddresses'][0]['emailAddress']) ? 
                    $lexofficeData['emailAddresses'][0]['emailAddress'] : $customer->email,
                'phone' => isset($lexofficeData['phoneNumbers'][0]['phoneNumber']) ? 
                    $lexofficeData['phoneNumbers'][0]['phoneNumber'] : $customer->phone,
                'lexoffice_synced_at' => now(),
            ];

            // Alle Adressen von Lexoffice importieren
            $addressesImported = $this->importCustomerAddressesFromLexoffice($customer, $lexofficeData['addresses'] ?? []);

            // Standard-Adresse (Primary) in Customer-Tabelle aktualisieren
            $primaryAddress = null;
            foreach ($lexofficeData['addresses'] ?? [] as $address) {
                if (isset($address['isPrimary']) && $address['isPrimary']) {
                    $primaryAddress = $address;
                    break;
                }
            }
            
            // Falls keine Primary-Adresse gefunden, erste Adresse verwenden (nur wenn sie Daten enthält)
            if (!$primaryAddress && !empty($lexofficeData['addresses'])) {
                foreach ($lexofficeData['addresses'] as $address) {
                    if (!empty($address['street']) || !empty($address['city']) || !empty($address['zip'])) {
                        $primaryAddress = $address;
                        break;
                    }
                }
            }
            
            if ($primaryAddress) {
                $updateData['street'] = $primaryAddress['street'] ?? $customer->street;
                $updateData['postal_code'] = $primaryAddress['zip'] ?? $customer->postal_code;
                $updateData['city'] = $primaryAddress['city'] ?? $customer->city;
                $updateData['country'] = $this->mapCountryCodeToName($primaryAddress['countryCode'] ?? 'DE');
                $updateData['country_code'] = $primaryAddress['countryCode'] ?? 'DE';
                $updateData['address_line_2'] = $primaryAddress['supplement'] ?? null;
            }

            // Kunde aktualisieren
            $customer->update($updateData);

            $this->logAction('customer', 'import', $customer->id, $customer->lexoffice_id, $lexofficeData, 'success');

            $message = 'Kunde erfolgreich von Lexoffice importiert';
            if ($addressesImported > 0) {
                $message .= " ({$addressesImported} Adresse(n) importiert)";
            }

            return [
                'success' => true,
                'action' => 'import_update',
                'message' => $message,
                'updated_fields' => array_keys($updateData),
                'addresses_imported' => $addressesImported
            ];

        } catch (\Exception $e) {
            $this->logAction('customer', 'import', $customer->id, $customer->lexoffice_id, $lexofficeData, 'error', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Adressen von Lexoffice importieren und in Address-Tabelle speichern
     */
    private function importCustomerAddressesFromLexoffice(Customer $customer, array $lexofficeAddresses): int
    {
        $importedCount = 0;
        
        // Neue Lexoffice-Struktur: addresses.billing[] und addresses.shipping[]
        if (isset($lexofficeAddresses['billing']) && is_array($lexofficeAddresses['billing'])) {
            foreach ($lexofficeAddresses['billing'] as $billingAddress) {
                $addressData = [
                    'addressable_id' => $customer->id,
                    'addressable_type' => Customer::class,
                    'type' => 'billing',
                    'street_address' => $billingAddress['street'] ?? '',
                    'postal_code' => $billingAddress['zip'] ?? '',
                    'city' => $billingAddress['city'] ?? '',
                    'state' => null,
                    'country' => $this->mapCountryCodeToName($billingAddress['countryCode'] ?? 'DE'),
                ];
                
                // Nur importieren wenn Pflichtfelder vorhanden sind
                if (!empty($addressData['street_address']) && !empty($addressData['city']) && !empty($addressData['postal_code'])) {
                    // Prüfe ob bereits eine billing-Adresse existiert
                    $existingAddress = $customer->addresses()->where('type', 'billing')->first();
                    
                    if ($existingAddress) {
                        $existingAddress->update($addressData);
                    } else {
                        \App\Models\Address::create($addressData);
                    }
                    
                    $importedCount++;
                }
            }
        }
        
        // Shipping-Adressen importieren
        if (isset($lexofficeAddresses['shipping']) && is_array($lexofficeAddresses['shipping'])) {
            foreach ($lexofficeAddresses['shipping'] as $shippingAddress) {
                $addressData = [
                    'addressable_id' => $customer->id,
                    'addressable_type' => Customer::class,
                    'type' => 'shipping',
                    'street_address' => $shippingAddress['street'] ?? '',
                    'postal_code' => $shippingAddress['zip'] ?? '',
                    'city' => $shippingAddress['city'] ?? '',
                    'state' => null,
                    'country' => $this->mapCountryCodeToName($shippingAddress['countryCode'] ?? 'DE'),
                ];
                
                // Nur importieren wenn Pflichtfelder vorhanden sind
                if (!empty($addressData['street_address']) && !empty($addressData['city']) && !empty($addressData['postal_code'])) {
                    // Prüfe ob bereits eine shipping-Adresse existiert
                    $existingAddress = $customer->addresses()->where('type', 'shipping')->first();
                    
                    if ($existingAddress) {
                        $existingAddress->update($addressData);
                    } else {
                        \App\Models\Address::create($addressData);
                    }
                    
                    $importedCount++;
                }
            }
        }
        
        return $importedCount;
    }

    /**
     * Ländercode zu Ländername konvertieren
     */
    private function mapCountryCodeToName(string $countryCode): string
    {
        $countryMap = [
            'DE' => 'Deutschland',
            'AT' => 'Österreich',
            'CH' => 'Schweiz',
            'FR' => 'Frankreich',
            'IT' => 'Italien',
            'NL' => 'Niederlande',
            'BE' => 'Belgien',
            'LU' => 'Luxemburg',
            'DK' => 'Dänemark',
            'SE' => 'Schweden',
            'NO' => 'Norwegen',
            'FI' => 'Finnland',
            'PL' => 'Polen',
            'CZ' => 'Tschechien',
            'SK' => 'Slowakei',
            'HU' => 'Ungarn',
            'SI' => 'Slowenien',
            'HR' => 'Kroatien',
            'ES' => 'Spanien',
            'PT' => 'Portugal',
            'GB' => 'Vereinigtes Königreich',
            'IE' => 'Irland',
            'US' => 'Vereinigte Staaten',
            'CA' => 'Kanada',
            'AU' => 'Australien',
            'NZ' => 'Neuseeland',
            'JP' => 'Japan',
            'KR' => 'Südkorea',
            'CN' => 'China',
            'IN' => 'Indien',
            'BR' => 'Brasilien',
            'MX' => 'Mexiko',
            'AR' => 'Argentinien',
            'CL' => 'Chile',
            'ZA' => 'Südafrika',
            'EG' => 'Ägypten',
            'IL' => 'Israel',
            'TR' => 'Türkei',
            'RU' => 'Russland',
            'UA' => 'Ukraine',
            'BY' => 'Weißrussland',
            'RS' => 'Serbien',
            'BA' => 'Bosnien und Herzegowina',
            'ME' => 'Montenegro',
            'MK' => 'Nordmazedonien',
            'AL' => 'Albanien',
            'BG' => 'Bulgarien',
            'RO' => 'Rumänien',
            'MD' => 'Moldau',
            'LT' => 'Litauen',
            'LV' => 'Lettland',
            'EE' => 'Estland',
            'GR' => 'Griechenland',
            'CY' => 'Zypern',
            'MT' => 'Malta',
            'IS' => 'Island',
        ];
        
        return $countryMap[$countryCode] ?? 'Deutschland';
    }

    /**
     * Kunde erstellen oder aktualisieren
     */
    private function createOrUpdateCustomer(array $lexofficeData): Customer
    {
        // Bestimme Kundentyp und Name basierend auf Lexoffice-Daten
        $customerType = 'private'; // Standard: Privatkunde
        $name = '';
        $companyName = null;
        
        if (isset($lexofficeData['company']['name'])) {
            // Firmenkunde
            $customerType = 'business';
            $name = $lexofficeData['company']['name'];
            $companyName = $lexofficeData['company']['name'];
        } elseif (isset($lexofficeData['person'])) {
            // Privatkunde
            $customerType = 'private';
            $firstName = $lexofficeData['person']['firstName'] ?? '';
            $lastName = $lexofficeData['person']['lastName'] ?? '';
            $name = trim($firstName . ' ' . $lastName);
        }
        
        if (empty($name)) {
            $name = 'Unbekannter Kunde';
        }

        $customerData = [
            'name' => $name,
            'customer_type' => $customerType,
            'company_name' => $companyName,
            'email' => $lexofficeData['emailAddresses'][0]['emailAddress'] ?? null,
            'phone' => $lexofficeData['phoneNumbers'][0]['phoneNumber'] ?? null,
            'lexoffice_id' => $lexofficeData['id'],
            'lexoffice_synced_at' => now(),
            'country' => 'DE', // Standard-Land setzen (DB-Constraint)
            'lexware_version' => $lexofficeData['version'] ?? null,
            'lexware_json' => $lexofficeData,
        ];

        // Adresse extrahieren
        if (isset($lexofficeData['addresses'][0])) {
            $address = $lexofficeData['addresses'][0];
            $customerData['street'] = $address['street'] ?? null;
            $customerData['postal_code'] = $address['zip'] ?? null;
            $customerData['city'] = $address['city'] ?? null;
            $customerData['country'] = $address['countryCode'] ?? 'DE';
        }

        // Prüfen ob Kunde bereits existiert (zuerst nach Lexoffice ID, dann nach Name)
        $existingCustomer = Customer::where('lexoffice_id', $lexofficeData['id'])->first();
        
        if ($existingCustomer) {
            // Kunde mit gleicher Lexoffice ID gefunden - aktualisieren
            $existingCustomer->update($customerData);
            
            // Adressen auch bei bestehenden Kunden importieren (falls noch nicht vorhanden)
            $this->importCustomerAddressesFromLexoffice($existingCustomer, $lexofficeData['addresses'] ?? []);
            
            return $existingCustomer;
        }
        
        // Prüfen ob bereits ein Kunde mit gleichem Namen existiert (ohne Lexoffice ID)
        $customerByName = Customer::whereNull('lexoffice_id')
                                ->where('name', $name)
                                ->first();
        
        if ($customerByName) {
            // Bestehenden Kunde mit Lexoffice ID verknüpfen und aktualisieren
            $customerByName->update($customerData);
            
            // Adressen importieren
            $this->importCustomerAddressesFromLexoffice($customerByName, $lexofficeData['addresses'] ?? []);
            
            return $customerByName;
        }
        
        // Neuen Kunde erstellen - customer_number wird automatisch generiert durch Model Boot
        $newCustomer = Customer::create($customerData);
        
        // Adressen für neuen Kunden importieren
        $this->importCustomerAddressesFromLexoffice($newCustomer, $lexofficeData['addresses'] ?? []);
        
        return $newCustomer;
    }

    /**
     * Artikel erstellen oder aktualisieren
     */
    private function createOrUpdateArticle(array $lexofficeData): Article
    {
        $netPrice = $lexofficeData['price']['netPrice'] ?? 0.01;
        
        // Negative Preise erlauben, nur bei positiven Preisen Mindestpreis setzen
        if ($netPrice > 0 && $netPrice < 0.01) {
            $netPrice = 0.01;
        }
        
        $articleData = [
            'name' => $lexofficeData['title'],
            'description' => $lexofficeData['description'] ?? null,
            'type' => $lexofficeData['type'] ?? 'SERVICE',
            'price' => $netPrice,
            'tax_rate' => ($lexofficeData['price']['taxRate'] ?? 19) / 100,
            'lexoffice_id' => $lexofficeData['id'],
        ];

        return Article::updateOrCreate(
            ['lexoffice_id' => $lexofficeData['id']],
            $articleData
        );
    }

    /**
     * Artikeldaten für Lexoffice vorbereiten
     */
    private function prepareArticleData(Article $article): array
    {
        $data = [
            'title' => $article->name,
            'description' => $article->description ?? '',
            'type' => $article->type ?? 'SERVICE',
            'unitName' => 'Stück', // Pflichtfeld für Lexoffice
        ];
        
        // Preis korrekt behandeln - negative Preise erlauben (z.B. Einspeisevergütung)
        $netPrice = round($article->price, 2);
        
        // Nur bei positiven Preisen Mindestpreis von 0.01 setzen
        if ($netPrice > 0 && $netPrice < 0.01) {
            $netPrice = 0.01;
        }
        
        $taxRate = round($article->tax_rate * 100, 2);
        
        $data['price'] = [
            'currency' => 'EUR',
            'netPrice' => $netPrice,
            'taxRate' => $taxRate,
            'leadingPrice' => 'NET', // Pflichtfeld: NET oder GROSS
        ];
        
        return $data;
    }

    /**
     * Kundendaten für Lexoffice vorbereiten
     */
    private function prepareCustomerData(Customer $customer): array
    {
        // Validierung der Pflichtfelder
        if (empty(trim($customer->name))) {
            throw new \InvalidArgumentException('Kundenname ist erforderlich');
        }

        // Basis-Struktur nach Lexoffice API v1 Dokumentation
        // Customer-Role muss ein Objekt sein - verwende stdClass für korrektes JSON
        $data = [
            'roles' => [
                'customer' => new \stdClass()
            ]
        ];

        // Firmenname oder Personenname (Pflichtfeld)
        $customerName = trim($customer->name);
        
        if (str_contains(strtolower($customerName), 'gmbh') ||
            str_contains(strtolower($customerName), 'ag') ||
            str_contains(strtolower($customerName), 'kg') ||
            str_contains(strtolower($customerName), 'ltd') ||
            str_contains(strtolower($customerName), 'inc') ||
            str_contains(strtolower($customerName), 'ug')) {
            // Firma
            $data['company'] = [
                'name' => $customerName
            ];
        } else {
            // Person - Name aufteilen
            $nameParts = explode(' ', $customerName, 2);
            $firstName = trim($nameParts[0] ?? '');
            $lastName = trim($nameParts[1] ?? '');
            
            // Mindestens Vorname muss vorhanden sein
            if (empty($firstName)) {
                $firstName = $customerName;
                $lastName = '';
            }
            
            $data['person'] = [
                'firstName' => $firstName,
                'lastName' => $lastName
            ];
        }

        // E-Mail hinzufügen (optional, aber wenn vorhanden dann korrekt strukturiert)
        if (!empty($customer->email) && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            $data['emailAddresses'] = [
                [
                    'emailAddress' => $customer->email,
                    'isPrimary' => true
                ]
            ];
        }

        // Telefon hinzufügen (optional, aber bereinigt)
        if (!empty($customer->phone)) {
            // Telefonnummer bereinigen (nur Zahlen, +, -, Leerzeichen, Klammern)
            $cleanPhone = preg_replace('/[^0-9+\-\s()]/', '', $customer->phone);
            if (!empty($cleanPhone)) {
                $data['phoneNumbers'] = [
                    [
                        'phoneNumber' => $cleanPhone,
                        'isPrimary' => true
                    ]
                ];
            }
        }

        // Alle Adressen sammeln und zu Lexoffice senden
        $addresses = $this->prepareCustomerAddresses($customer);
        if (!empty($addresses)) {
            $data['addresses'] = $addresses;
        }

        return $data;
    }

    /**
     * Alle Adressen eines Kunden für Lexoffice vorbereiten
     */
    private function prepareCustomerAddresses(Customer $customer): array
    {
        $addresses = [];
        $hasPrimary = false;
        
        // 1. Standard-Adresse (aus Customer-Tabelle) - immer als Primary wenn vorhanden
        if (!empty($customer->street) && !empty($customer->city) && !empty($customer->postal_code)) {
            $address = $this->formatAddressForLexoffice(
                $customer->street,
                $customer->address_line_2,
                $customer->postal_code,
                $customer->city,
                $customer->state,
                $customer->country,
                true // isPrimary
            );
            
            if ($address) {
                $addresses[] = $address;
                $hasPrimary = true;
            }
        }
        
        // 2. Separate Rechnungsadresse
        $billingAddress = $customer->billingAddress;
        if ($billingAddress && !empty($billingAddress->street_address) && !empty($billingAddress->city) && !empty($billingAddress->postal_code)) {
            $address = $this->formatAddressForLexoffice(
                $billingAddress->street_address,
                null, // address_line_2 nicht im Address-Model
                $billingAddress->postal_code,
                $billingAddress->city,
                $billingAddress->state,
                $billingAddress->country,
                !$hasPrimary // Primary wenn noch keine Standard-Adresse vorhanden
            );
            
            if ($address) {
                $addresses[] = $address;
                if (!$hasPrimary) {
                    $hasPrimary = true;
                }
            }
        }
        
        // 3. Separate Lieferadresse
        $shippingAddress = $customer->shippingAddress;
        if ($shippingAddress && !empty($shippingAddress->street_address) && !empty($shippingAddress->city) && !empty($shippingAddress->postal_code)) {
            $address = $this->formatAddressForLexoffice(
                $shippingAddress->street_address,
                null, // address_line_2 nicht im Address-Model
                $shippingAddress->postal_code,
                $shippingAddress->city,
                $shippingAddress->state,
                $shippingAddress->country,
                !$hasPrimary // Primary wenn noch keine andere Primary-Adresse vorhanden
            );
            
            if ($address) {
                $addresses[] = $address;
                if (!$hasPrimary) {
                    $hasPrimary = true;
                }
            }
        }
        
        return $addresses;
    }

    /**
     * Einzelne Adresse für Lexoffice formatieren
     */
    private function formatAddressForLexoffice(
        ?string $street,
        ?string $addressLine2,
        ?string $postalCode,
        ?string $city,
        ?string $state,
        ?string $country,
        bool $isPrimary = false
    ): ?array {
        // Validierung der Pflichtfelder
        if (empty($street) || empty($city) || empty($postalCode)) {
            return null;
        }
        
        // PLZ validieren (deutsche PLZ: 5 Ziffern)
        $cleanPostalCode = preg_replace('/[^0-9]/', '', $postalCode);
        if (strlen($cleanPostalCode) !== 5) {
            return null;
        }
        
        // Ländercode bestimmen
        $countryCode = 'DE'; // Standard Deutschland
        if (!empty($country)) {
            if (strtolower($country) === 'deutschland' || strtolower($country) === 'germany') {
                $countryCode = 'DE';
            } elseif (strlen($country) === 2) {
                $countryCode = strtoupper($country);
            }
        }
        
        // Adresse zusammenstellen
        $address = [
            'street' => trim($street),
            'supplement' => trim($addressLine2 ?? ''), // Lexoffice erwartet dieses Feld
            'zip' => $cleanPostalCode,
            'city' => trim($city),
            'countryCode' => $countryCode,
            'isPrimary' => (bool) $isPrimary // Explizit als boolean casten
        ];
        
        return $address;
    }

    /**
     * Rechnungsdaten für Lexoffice vorbereiten
     */
    private function prepareInvoiceData(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        $items = $invoice->items()->with('article')->get();

        return [
            'type' => 'invoice',
            'address' => [
                'name' => $customer->name,
                'street' => $customer->street,
                'zip' => $customer->postal_code,
                'city' => $customer->city,
                'countryCode' => $customer->country === 'Deutschland' ? 'DE' : $customer->country,
            ],
            'lineItems' => $items->map(function ($item) {
                return [
                    'type' => 'custom',
                    'name' => $item->article->name,
                    'description' => $item->description ?: $item->article->description,
                    'quantity' => (float) $item->quantity,
                    'unitName' => 'Stück',
                    'unitPrice' => [
                        'currency' => 'EUR',
                        'netAmount' => round($item->unit_price, 2), // Auf 2 Nachkommastellen runden
                        'taxRatePercentage' => $item->tax_rate * 100,
                    ],
                ];
            })->toArray(),
            'totalPrice' => [
                'currency' => 'EUR',
            ],
            'taxConditions' => [
                'taxType' => 'net',
            ],
            'paymentConditions' => [
                'paymentTermLabel' => 'Zahlbar innerhalb von 14 Tagen',
                'paymentTermDuration' => 14,
            ],
            'introduction' => 'Vielen Dank für Ihren Auftrag.',
            'remark' => 'Bei Fragen stehen wir Ihnen gerne zur Verfügung.',
        ];
    }

    /**
     * Fehlermeldung aus Exception extrahieren
     */
    private function extractErrorMessage(RequestException $e): string
    {
        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = $e->getResponse()->getBody()->getContents();
            
            // Versuche JSON zu parsen
            $response = json_decode($responseBody, true);
            
            // Basis-Fehlermeldung
            $errorMessage = "HTTP {$statusCode}";
            
            if ($response && is_array($response)) {
                // Lexoffice-spezifische Fehlermeldung
                if (isset($response['message'])) {
                    $errorMessage = $response['message'];
                } elseif (isset($response['error'])) {
                    $errorMessage = $response['error'];
                } elseif (isset($response['error_description'])) {
                    $errorMessage = $response['error_description'];
                }
                
                // Details hinzufügen falls vorhanden
                if (isset($response['details']) && is_array($response['details'])) {
                    $details = [];
                    foreach ($response['details'] as $detail) {
                        if (is_array($detail)) {
                            $details[] = implode(': ', $detail);
                        } else {
                            $details[] = (string) $detail;
                        }
                    }
                    if (!empty($details)) {
                        $errorMessage .= ' | Details: ' . implode(', ', $details);
                    }
                }
                
                // Validierungsfehler hinzufügen
                if (isset($response['violations']) && is_array($response['violations'])) {
                    $violations = [];
                    foreach ($response['violations'] as $violation) {
                        if (isset($violation['field']) && isset($violation['message'])) {
                            $violations[] = $violation['field'] . ': ' . $violation['message'];
                        }
                    }
                    if (!empty($violations)) {
                        $errorMessage .= ' | Validierungsfehler: ' . implode(', ', $violations);
                    }
                }
            } else {
                // Fallback für nicht-JSON Antworten
                $errorMessage .= ': ' . substr($responseBody, 0, 200);
            }
            
            // Vollständige Response für Debugging loggen
            Log::error('Lexoffice API Error', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'parsed_response' => $response,
                'request_url' => $e->getRequest()->getUri()->__toString(),
                'request_method' => $e->getRequest()->getMethod()
            ]);
            
            return $errorMessage;
        }
        
        return $e->getMessage();
    }

    /**
     * Aktuelle Version eines Kontakts sicher abrufen
     */
    private function getCurrentContactVersion(string $contactId): ?array
    {
        try {
            $response = $this->client->get("contacts/{$contactId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'version' => $data['version'] ?? null,
                'updatedDate' => $data['updatedDate'] ?? null,
                'data' => $data
            ];
        } catch (RequestException $e) {
            $this->logAction('contact', 'version_check', null, $contactId, null, 'error', $e->getMessage());
            return null;
        }
    }

    /**
     * Kundendaten für PUT-Update vorbereiten (mit ID und organizationId)
     */
    private function prepareCustomerDataForUpdate(Customer $customer, array $lexofficeData): array
    {
        // Basis-Kundendaten vorbereiten
        $customerData = $this->prepareCustomerData($customer);
        
        // Erforderliche Felder für PUT-Request hinzufügen
        $customerData['id'] = $lexofficeData['id'];
        $customerData['organizationId'] = $lexofficeData['organizationId'];
        $customerData['version'] = $lexofficeData['version'];
        
        // Log für Debugging
        Log::info('PUT Request Data prepared', [
            'id' => $customerData['id'],
            'organizationId' => $customerData['organizationId'],
            'version' => $customerData['version'],
            'customer_name' => $customer->name
        ]);
        
        return $customerData;
    }

    /**
     * Version-spezifische Operationen loggen
     */
    private function logVersionOperation(
        string $operation,
        Customer $customer,
        ?int $expectedVersion = null,
        ?int $actualVersion = null,
        ?int $attempt = null,
        ?int $maxAttempts = null,
        ?string $error = null
    ): void {
        $versionData = [
            'expected_version' => $expectedVersion,
            'actual_version' => $actualVersion,
            'version_difference' => $actualVersion && $expectedVersion ? 
                $actualVersion - $expectedVersion : null,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'success_rate' => $attempt && $maxAttempts ? 
                round((1 - ($attempt - 1) / $maxAttempts) * 100, 2) : null
        ];

        $this->logAction(
            'customer',
            "version_{$operation}",
            $customer->id,
            $customer->lexoffice_id,
            $versionData,
            $error ? 'error' : 'success',
            $error,
            null,
            ['version_operation' => $operation]
        );
    }

    /**
     * Performance-Metriken loggen
     */
    private function logPerformanceMetrics(string $operation, array $metrics): void
    {
        $this->logAction(
            'performance',
            $operation,
            null,
            null,
            $metrics,
            'info',
            null,
            null,
            [
                'performance_category' => 'api_timing',
                'benchmark_data' => $metrics
            ]
        );
    }

    /**
     * Erweiterte Aktion in Log-Tabelle speichern
     */
    private function logAction(
        string $type,
        string $action,
        ?string $entityId,
        ?string $lexofficeId,
        ?array $requestData,
        string $status,
        ?string $errorMessage = null,
        ?array $responseData = null,
        ?array $additionalContext = []
    ): void {
        // Timing-Informationen und Kontext hinzufügen
        $context = array_merge([
            'timestamp' => now()->toISOString(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip() ?? 'cli',
            'session_id' => session()->getId() ?? 'cli'
        ], $additionalContext);

        // Erweiterte Request-Daten für besseres Debugging
        $enhancedRequestData = $requestData;
        if ($requestData && $type === 'customer' && str_contains($action, 'version')) {
            $enhancedRequestData['context'] = $context;
        }

        LexofficeLog::create([
            'type' => $type,
            'action' => $action,
            'entity_id' => $entityId,
            'lexoffice_id' => $lexofficeId,
            'request_data' => $enhancedRequestData,
            'response_data' => $responseData,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Lexware-Daten von Lexoffice abrufen und in Customer speichern
     */
    public function fetchAndStoreLexwareData(Customer $customer): array
    {
        if (!$customer->lexoffice_id) {
            return [
                'success' => false,
                'error' => 'Kunde hat keine Lexoffice-ID'
            ];
        }

        $startTime = microtime(true);

        try {
            // GET Request zu Lexoffice
            $response = $this->client->get("contacts/{$customer->lexoffice_id}");
            $lexwareData = json_decode($response->getBody()->getContents(), true);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Version und JSON-Daten in Customer speichern
            $customer->update([
                'lexware_version' => $lexwareData['version'] ?? null,
                'lexware_json' => $lexwareData,
                'lexoffice_synced_at' => now()
            ]);

            // Erfolg loggen
            $this->logAction(
                'customer',
                'fetch_lexware_data',
                $customer->id,
                $customer->lexoffice_id,
                ['requested_fields' => ['version', 'full_data']],
                'success',
                null,
                [
                    'version' => $lexwareData['version'] ?? null,
                    'data_size_bytes' => strlen(json_encode($lexwareData)),
                    'duration_ms' => $duration
                ],
                [
                    'action_type' => 'fetch_and_store',
                    'duration_ms' => $duration
                ]
            );

            return [
                'success' => true,
                'version' => $lexwareData['version'] ?? null,
                'data' => $lexwareData,
                'message' => 'Lexware-Daten erfolgreich abgerufen und gespeichert',
                'duration_ms' => $duration
            ];

        } catch (RequestException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $errorMessage = $this->extractErrorMessage($e);

            // Fehler loggen
            $this->logAction(
                'customer',
                'fetch_lexware_data',
                $customer->id,
                $customer->lexoffice_id,
                ['requested_fields' => ['version', 'full_data']],
                'error',
                $errorMessage,
                null,
                [
                    'duration_ms' => $duration,
                    'http_status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
                ]
            );

            return [
                'success' => false,
                'error' => $errorMessage,
                'duration_ms' => $duration
            ];
        }
    }

    /**
     * Kunde direkt mit gespeicherter Lexware-Version zu Lexoffice exportieren
     * Verwendet für Adress-Updates über Popups
     */
    public function exportCustomerWithStoredVersion(Customer $customer): array
    {
        if (!$customer->lexoffice_id) {
            return [
                'success' => false,
                'error' => 'Kunde hat keine Lexoffice-ID'
            ];
        }

        if (!$customer->lexware_version) {
            return [
                'success' => false,
                'error' => 'Keine Lexware-Version gespeichert. Bitte zuerst Lexware-Daten abrufen.'
            ];
        }

        $startTime = microtime(true);

        try {
            // Verwende die gespeicherte Lexware-Version und JSON-Daten
            $lexwareData = $customer->lexware_json;
            
            if (!$lexwareData) {
                return [
                    'success' => false,
                    'error' => 'Keine Lexware-JSON-Daten gespeichert. Bitte zuerst Lexware-Daten abrufen.'
                ];
            }

            // Kundendaten für PUT-Update vorbereiten mit gespeicherter Version
            $customerData = $this->prepareCustomerDataForStoredVersion($customer, $lexwareData);

            $this->logVersionOperation('direct_update_attempt', $customer, $customer->lexware_version, $customer->lexware_version, 1, 1);

            // Log Request-Daten
            Log::info('Lexoffice PUT Request (Stored Version)', [
                'url' => "PUT contacts/{$customer->lexoffice_id}",
                'request_data' => $customerData,
                'stored_version' => $customer->lexware_version,
                'customer_id' => $customer->id
            ]);

            $response = $this->client->put("contacts/{$customer->lexoffice_id}", [
                'json' => $customerData
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log Response-Daten
            Log::info('Lexoffice Response (Stored Version)', [
                'status' => $response->getStatusCode(),
                'response_data' => $responseData,
                'duration_ms' => $duration
            ]);

            // Neue Version und JSON-Daten speichern
            $customer->update([
                'lexware_version' => $responseData['version'] ?? null,
                'lexware_json' => $responseData,
                'lexoffice_synced_at' => now()
            ]);

            // Erfolgreiche Version-Operation loggen
            $this->logVersionOperation('direct_update_success', $customer, $customer->lexware_version, $responseData['version'] ?? null, 1, 1);

            $this->logAction('customer', 'export_stored_version', $customer->id, $customer->lexoffice_id, $customerData, 'success', null, $responseData, [
                'action_type' => 'direct_update_with_stored_version',
                'duration_ms' => $duration,
                'used_version' => $customer->lexware_version
            ]);

            return [
                'success' => true,
                'action' => 'direct_update_with_stored_version',
                'lexoffice_id' => $responseData['id'],
                'old_version' => $customer->lexware_version,
                'new_version' => $responseData['version'] ?? null,
                'message' => 'Kunde erfolgreich mit gespeicherter Version zu Lexoffice exportiert',
                'data' => $responseData,
                'duration_ms' => $duration
            ];

        } catch (RequestException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log Error-Daten
            $errorData = [
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'No Response',
                'error_message' => $e->getMessage(),
                'duration_ms' => $duration,
                'used_version' => $customer->lexware_version
            ];

            if ($e->hasResponse()) {
                $errorData['error_response'] = $e->getResponse()->getBody()->getContents();
            }

            Log::error('Lexoffice Error (Stored Version)', $errorData);

            // Version-Fehler loggen
            $this->logVersionOperation('direct_update_failed', $customer, $customer->lexware_version, null, 1, 1, $e->getMessage());

            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('customer', 'export_stored_version', $customer->id, $customer->lexoffice_id, $customerData ?? null, 'error', $errorMessage, null, [
                'duration_ms' => $duration,
                'http_status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'used_version' => $customer->lexware_version
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'duration_ms' => $duration,
                'used_version' => $customer->lexware_version
            ];
        }
    }

    /**
     * Kundendaten für PUT-Update mit gespeicherten Lexware-Daten vorbereiten
     */
    private function prepareCustomerDataForStoredVersion(Customer $customer, array $lexwareData): array
    {
        // Verwende die gespeicherten JSON-Daten als Basis und aktualisiere nur die geänderten Felder
        $customerData = $lexwareData;
        
        // Setze die gespeicherte Version
        $customerData['version'] = $customer->lexware_version;
        
        // Aktualisiere Kundendaten basierend auf lokalem Customer-Typ
        if ($customer->customer_type === 'business') {
            // Firmenkunde
            $customerData['company'] = [
                'name' => $customer->company_name ?: $customer->name
            ];
            
            // Entferne person-Daten falls vorhanden
            unset($customerData['person']);
            
            // Kundennummer in roles setzen
            if (!isset($customerData['roles']['customer']['number']) && $customer->customer_number) {
                $customerData['roles']['customer']['number'] = (int) $customer->customer_number;
            }
        } else {
            // Privatkunde
            $nameParts = explode(' ', $customer->name, 2);
            $customerData['person'] = [
                'salutation' => $customerData['person']['salutation'] ?? '',
                'firstName' => $nameParts[0] ?? '',
                'lastName' => $nameParts[1] ?? ''
            ];
            
            // Entferne company-Daten falls vorhanden
            unset($customerData['company']);
        }
        
        // Aktualisiere Adressen mit aktuellen lokalen Daten
        $customerData['addresses'] = [];
        
        // Rechnungsadresse
        $billingAddress = $customer->billingAddress;
        if ($billingAddress) {
            $customerData['addresses']['billing'][] = [
                'street' => $billingAddress->street_address,
                'zip' => $billingAddress->postal_code,
                'city' => $billingAddress->city,
                'countryCode' => $this->getCountryCode($billingAddress->country)
            ];
        } else {
            // Verwende Standard-Adresse als Rechnungsadresse
            if ($customer->street && $customer->city && $customer->postal_code) {
                $customerData['addresses']['billing'][] = [
                    'street' => $customer->street,
                    'zip' => $customer->postal_code,
                    'city' => $customer->city,
                    'countryCode' => $customer->country_code ?: 'DE'
                ];
            }
        }
        
        // Lieferadresse
        $shippingAddress = $customer->shippingAddress;
        if ($shippingAddress) {
            $customerData['addresses']['shipping'][] = [
                'street' => $shippingAddress->street_address,
                'zip' => $shippingAddress->postal_code,
                'city' => $shippingAddress->city,
                'countryCode' => $this->getCountryCode($shippingAddress->country)
            ];
        }
        
        // Archiviert-Status
        $customerData['archived'] = !$customer->is_active;
        
        // Log für Debugging
        Log::info('PUT Request Data prepared (Stored Version)', [
            'id' => $customerData['id'],
            'organizationId' => $customerData['organizationId'],
            'version' => $customerData['version'],
            'customer_name' => $customer->name,
            'stored_version' => $customer->lexware_version,
            'addresses_count' => count($customerData['addresses'])
        ]);
        
        return $customerData;
    }

    /**
     * Ländercode aus Ländername extrahieren
     */
    private function getCountryCode(string $country): string
    {
        $countryMap = [
            'Deutschland' => 'DE',
            'Österreich' => 'AT',
            'Schweiz' => 'CH',
            'Frankreich' => 'FR',
            'Italien' => 'IT',
            'Niederlande' => 'NL',
            'Belgien' => 'BE',
            'Luxemburg' => 'LU',
            'Dänemark' => 'DK',
            'Schweden' => 'SE',
            'Norwegen' => 'NO',
            'Finnland' => 'FI',
            'Polen' => 'PL',
            'Tschechien' => 'CZ',
            'Slowakei' => 'SK',
            'Ungarn' => 'HU',
            'Slowenien' => 'SI',
            'Kroatien' => 'HR',
            'Spanien' => 'ES',
            'Portugal' => 'PT',
            'Vereinigtes Königreich' => 'GB',
            'Irland' => 'IE',
            'Vereinigte Staaten' => 'US',
            'Kanada' => 'CA',
            'Australien' => 'AU',
            'Neuseeland' => 'NZ',
            'Japan' => 'JP',
            'Südkorea' => 'KR',
            'China' => 'CN',
            'Indien' => 'IN',
            'Brasilien' => 'BR',
            'Mexiko' => 'MX',
            'Argentinien' => 'AR',
            'Chile' => 'CL',
            'Südafrika' => 'ZA',
            'Ägypten' => 'EG',
            'Israel' => 'IL',
            'Türkei' => 'TR',
            'Russland' => 'RU',
            'Ukraine' => 'UA',
            'Weißrussland' => 'BY',
            'Serbien' => 'RS',
            'Bosnien und Herzegowina' => 'BA',
            'Montenegro' => 'ME',
            'Nordmazedonien' => 'MK',
            'Albanien' => 'AL',
            'Bulgarien' => 'BG',
            'Rumänien' => 'RO',
            'Moldau' => 'MD',
            'Litauen' => 'LT',
            'Lettland' => 'LV',
            'Estland' => 'EE',
            'Griechenland' => 'GR',
            'Zypern' => 'CY',
            'Malta' => 'MT',
            'Island' => 'IS',
        ];
        
        // Direkte Zuordnung versuchen
        if (isset($countryMap[$country])) {
            return $countryMap[$country];
        }
        
        // Falls bereits ein 2-stelliger Code übergeben wurde
        if (strlen($country) === 2) {
            return strtoupper($country);
        }
        
        // Standard-Fallback
        return 'DE';
    }

    /**
     * API-Verbindung testen
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('profile');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'company' => $data['companyName'] ?? 'Unbekannt',
                'email' => $data['email'] ?? 'Unbekannt'
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e)
            ];
        }
    }
}
