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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'agora' => [
        'app_id'          => env('AGORA_APP_ID'),
        'app_certificate' => env('AGORA_APP_CERTIFICATE'),
        'token_expire'    => env('AGORA_TOKEN_EXPIRE', 3600),
        'customer_id'    => env('AGORA_CUSTOMER_ID'),
        'customer_secret'    => env('AGORA_CUSTOMER_SECRET'),
        'region'    => env('AGORA_REGION'),
    ],

    'sud' => [
        'app_id' => env('SUD_APP_ID'),
        'app_key' => env('SUD_APP_KEY'),
        'app_secret' => env('SUD_APP_SECRET'),
    ],

    'phonepe' => [
        'merchant_id'    => env('PHONEPE_MERCHANT_ID'),
        'client_id'      => env('PHONEPE_CLIENT_ID'),
        'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
        'client_version' => env('PHONEPE_CLIENT_VERSION', 1),
        'callback'       => env('PHONEPE_CALLBACK_URL'),
    ],

];
