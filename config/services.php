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

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ],

    /*
     * Read through config rather than calling env() at runtime. env() returns
     * null once config is cached, which would silently break every payment.
     */
    'razorpay' => [
        'key' => env('RAZOR_KEY'),
        'secret' => env('RAZOR_SECRET'),
    ],

    'msg91' => [
        'auth_token' => env('MSG91_AUTH_TOKEN'),
        'whatsapp_number' => env('MSG91_WHATSAPP_NUMBER'),

        /*
         * Whether messages actually leave the building.
         *
         * Unset means "only from production", so a developer working against a
         * copy of live data cannot WhatsApp real customers. Everywhere else the
         * message is written to the log instead, including OTPs, which is how
         * you complete a signup locally. Set SMS_ENABLED=true to force real
         * sending, or false to silence it in production too.
         */
        'enabled' => env('SMS_ENABLED'),
    ],

    'twofactor' => [
        'key' => env('2FA_SMS_API_KEY'),
    ],

];
