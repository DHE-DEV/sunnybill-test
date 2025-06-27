<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Article;
use App\Models\Invoice;
use App\Models\LexofficeLog;
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
        $this->apiKey = config('services.lexoffice.api_key');
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
     * Kunde zu Lexoffice exportieren
     */
    public function exportCustomer(Customer $customer): array
    {
        try {
            $customerData = $this->prepareCustomerData($customer);
            
            if ($customer->lexoffice_id) {
                // Kunde aktualisieren
                $response = $this->client->put("contacts/{$customer->lexoffice_id}", [
                    'json' => $customerData
                ]);
                $action = 'update';
            } else {
                // Neuen Kunde erstellen
                $response = $this->client->post('contacts', [
                    'json' => $customerData
                ]);
                $action = 'create';
            }

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Lexoffice ID in dem Kunden speichern
            $customer->update(['lexoffice_id' => $responseData['id']]);

            $this->logAction('customer', 'export', $customer->id, $responseData['id'], $customerData, 'success');

            return [
                'success' => true,
                'action' => $action,
                'lexoffice_id' => $responseData['id'],
                'data' => $responseData
            ];

        } catch (RequestException $e) {
            $errorMessage = $this->extractErrorMessage($e);
            $this->logAction('customer', 'export', $customer->id, null, null, 'error', $errorMessage);
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Kunde erstellen oder aktualisieren
     */
    private function createOrUpdateCustomer(array $lexofficeData): Customer
    {
        $customerData = [
            'name' => $lexofficeData['company']['name'] ?? $lexofficeData['person']['firstName'] . ' ' . $lexofficeData['person']['lastName'],
            'email' => $lexofficeData['emailAddresses'][0]['emailAddress'] ?? null,
            'phone' => $lexofficeData['phoneNumbers'][0]['phoneNumber'] ?? null,
            'lexoffice_id' => $lexofficeData['id'],
        ];

        // Adresse extrahieren
        if (isset($lexofficeData['addresses'][0])) {
            $address = $lexofficeData['addresses'][0];
            $customerData['street'] = $address['street'] ?? null;
            $customerData['postal_code'] = $address['zip'] ?? null;
            $customerData['city'] = $address['city'] ?? null;
            $customerData['country'] = $address['countryCode'] ?? 'DE';
        }

        return Customer::updateOrCreate(
            ['lexoffice_id' => $lexofficeData['id']],
            $customerData
        );
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
        // Basis-Struktur nach Lexoffice API v1 Dokumentation
        $data = [
            'roles' => [
                'customer' => (object)[] // Muss ein leeres Objekt sein, nicht Array!
            ]
        ];

        // Firmenname oder Personenname (Pflichtfeld)
        if (str_contains(strtolower($customer->name), 'gmbh') ||
            str_contains(strtolower($customer->name), 'ag') ||
            str_contains(strtolower($customer->name), 'kg') ||
            str_contains(strtolower($customer->name), 'ltd') ||
            str_contains(strtolower($customer->name), 'inc')) {
            // Firma
            $data['company'] = [
                'name' => $customer->name
            ];
        } else {
            // Person - Name aufteilen
            $nameParts = explode(' ', $customer->name, 2);
            $data['person'] = [
                'firstName' => $nameParts[0] ?? $customer->name,
                'lastName' => $nameParts[1] ?? ''
            ];
        }

        // E-Mail hinzufügen (optional, aber wenn vorhanden dann korrekt strukturiert)
        if ($customer->email && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            $data['emailAddresses'] = [
                [
                    'emailAddress' => $customer->email,
                    'isPrimary' => true
                ]
            ];
        }

        // Telefon hinzufügen (optional)
        if ($customer->phone) {
            $data['phoneNumbers'] = [
                [
                    'phoneNumber' => $customer->phone,
                    'isPrimary' => true
                ]
            ];
        }

        // Adresse hinzufügen (optional)
        if ($customer->street || $customer->city) {
            $data['addresses'] = [
                [
                    'street' => $customer->street ?? '',
                    'zip' => $customer->postal_code ?? '',
                    'city' => $customer->city ?? '',
                    'countryCode' => $customer->country === 'Deutschland' ? 'DE' : ($customer->country ?? 'DE'),
                    'isPrimary' => true
                ]
            ];
        }

        return $data;
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
     * Aktion in Log-Tabelle speichern
     */
    private function logAction(
        string $type,
        string $action,
        ?string $entityId,
        ?string $lexofficeId,
        ?array $requestData,
        string $status,
        ?string $errorMessage = null,
        ?array $responseData = null
    ): void {
        LexofficeLog::create([
            'type' => $type,
            'action' => $action,
            'entity_id' => $entityId,
            'lexoffice_id' => $lexofficeId,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
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