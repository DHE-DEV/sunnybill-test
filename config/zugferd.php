<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZUGFeRD Unternehmenseinstellungen
    |--------------------------------------------------------------------------
    |
    | Diese Einstellungen werden für die ZUGFeRD-Rechnungserstellung verwendet.
    | Stellen Sie sicher, dass alle Pflichtfelder ausgefüllt sind.
    |
    */

    'company' => [
        'name' => env('ZUGFERD_COMPANY_NAME', 'SunnyBill GmbH'),
        'id' => env('ZUGFERD_COMPANY_ID', 'DE123456789'),
        'vat_id' => env('ZUGFERD_COMPANY_VAT_ID', 'DE123456789'),
        'tax_number' => env('ZUGFERD_COMPANY_TAX_NUMBER', '123/456/78901'),
        
        'address' => [
            'street' => env('ZUGFERD_COMPANY_STREET', 'Musterstraße 1'),
            'zip' => env('ZUGFERD_COMPANY_ZIP', '12345'),
            'city' => env('ZUGFERD_COMPANY_CITY', 'Musterstadt'),
            'country' => env('ZUGFERD_COMPANY_COUNTRY', 'Deutschland'),
        ],
        
        'contact' => [
            'person' => env('ZUGFERD_COMPANY_CONTACT_PERSON', 'Max Mustermann'),
            'department' => env('ZUGFERD_COMPANY_DEPARTMENT', 'Vertrieb'),
            'phone' => env('ZUGFERD_COMPANY_PHONE', '+49 123 456789'),
            'email' => env('ZUGFERD_COMPANY_EMAIL', 'info@voltmaster.cloud'),
            'fax' => env('ZUGFERD_COMPANY_FAX', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ZUGFeRD Profil-Einstellungen
    |--------------------------------------------------------------------------
    |
    | Wählen Sie das gewünschte ZUGFeRD-Profil:
    | - BASIC: Grundlegende Rechnungsdaten
    | - EN16931: Vollständige EN16931-konforme Rechnungen (empfohlen)
    | - EXTENDED: Erweiterte Funktionen
    |
    */

    'profile' => env('ZUGFERD_PROFILE', 'EN16931'),

    /*
    |--------------------------------------------------------------------------
    | Validierung
    |--------------------------------------------------------------------------
    |
    | Aktivieren Sie die Validierung der generierten ZUGFeRD-XMLs
    |
    */

    'validation' => [
        'enabled' => env('ZUGFERD_VALIDATION_ENABLED', true),
        'strict_mode' => env('ZUGFERD_STRICT_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard-Werte
    |--------------------------------------------------------------------------
    |
    | Standard-Werte für fehlende Kundendaten
    |
    */

    'defaults' => [
        'customer' => [
            'street' => 'Musterstraße 1',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'country' => 'Deutschland',
        ],
        
        'payment_terms_days' => 14,
        'currency' => 'EUR',
    ],
];