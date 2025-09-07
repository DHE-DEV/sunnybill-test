<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Router Offline Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for router offline/online status changes.
    | You can specify multiple email addresses separated by commas.
    |
    */

    'enabled' => env('ROUTER_NOTIFICATION_ENABLED', true),

    'emails' => [
        'to' => env('ROUTER_NOTIFICATION_TO', ''),
        'cc' => env('ROUTER_NOTIFICATION_CC', ''),
        'bcc' => env('ROUTER_NOTIFICATION_BCC', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure when and how notifications are sent.
    |
    */

    'send_offline_notifications' => env('ROUTER_NOTIFICATION_OFFLINE', true),
    'send_online_notifications' => env('ROUTER_NOTIFICATION_ONLINE', true),
    'send_delayed_notifications' => env('ROUTER_NOTIFICATION_DELAYED', false),

    /*
    |--------------------------------------------------------------------------
    | Email Template Settings
    |--------------------------------------------------------------------------
    |
    | Configure the appearance and content of notification emails.
    |
    */

    'from_name' => env('ROUTER_NOTIFICATION_FROM_NAME', 'VoltMaster Monitoring'),
    'from_email' => env('ROUTER_NOTIFICATION_FROM_EMAIL', null), // Falls null, wird MAIL_FROM_ADDRESS verwendet

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    |
    | Advanced configuration options for notifications.
    |
    */

    'queue_notifications' => env('ROUTER_NOTIFICATION_QUEUE', true),
    'notification_cooldown_minutes' => env('ROUTER_NOTIFICATION_COOLDOWN', 60), // Verhindert Spam bei mehrfachen Status-Änderungen

];