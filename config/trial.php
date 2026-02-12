<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trial Popup Einstellungen
    |--------------------------------------------------------------------------
    |
    | Konfiguration für das Trial-Countdown-Popup, das Benutzern während
    | der Testphase angezeigt wird.
    |
    */

    'popup' => [
        'enabled' => env('TRIAL_POPUP_ENABLED', true),
        'countdown_seconds' => (int) env('TRIAL_POPUP_COUNTDOWN_SECONDS', 20),
        'end_date' => env('TRIAL_POPUP_END_DATE', '27.02.2026'),
        'frequency' => env('TRIAL_POPUP_FREQUENCY', 'once_per_session'),
    ],
];
