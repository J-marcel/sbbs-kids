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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    // 'twilio' => [
    //     'sid' => env('TWILIO_ACCOUNT_SID'),
    //     'token' => env('TWILIO_AUTH_TOKEN'),
    //     'phone_number' => env('TWILIO_PHONE_NUMBER'),
    //     'whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER', env('TWILIO_PHONE_NUMBER')),

    //     // Contrôles d'activation des services
    //     'sms_enabled' => env('TWILIO_SMS_ENABLED', true),
    //     'whatsapp_enabled' => env('TWILIO_WHATSAPP_ENABLED', false), // Désactivé par défaut
    // ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
        'whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER', env('TWILIO_PHONE_NUMBER')),
        'sms_enabled' => env('TWILIO_SMS_ENABLED', true),
        'whatsapp_enabled' => env('TWILIO_WHATSAPP_ENABLED', false),
    ],


'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
        'api_key' => env('INFOBIP_API_KEY'),
        'sender_name' => env('INFOBIP_SENDER_NAME', config('app.name')),
        'whatsapp_sender' => env('INFOBIP_WHATSAPP_SENDER'),
    ],

    'cinetpay' => [
    'api_key' => env('CINETPAY_API_KEY'),
    'site_id' => env('CINETPAY_SITE_ID'),
    'api_url' => env('CINETPAY_API_URL', 'https://api-checkout.cinetpay.com/v2/payment'),
],

];
