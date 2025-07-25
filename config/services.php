<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lexoffice' => [
        'api_key' => env('LEXOFFICE_API_KEY'),
        'base_url' => env('LEXOFFICE_BASE_URL', 'https://api.lexoffice.io/v1/'),
    ],

    'fusionsolar' => [
        'base_url' => env('FUSIONSOLAR_BASE_URL', 'https://eu5.fusionsolar.huawei.com/thirdData'),
        'username' => env('FUSIONSOLAR_USERNAME'),
        'password' => env('FUSIONSOLAR_PASSWORD'),
    ],

];
