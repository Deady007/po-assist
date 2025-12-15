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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'model_fast' => env('GEMINI_MODEL_FAST', 'gemini-1.5-flash'),
        'model_pro' => env('GEMINI_MODEL_PRO', 'gemini-2.5-FLASH'),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.3),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1200),
        'verify' => (bool) env('GEMINI_VERIFY_SSL', true),
        'ca_bundle' => env('GEMINI_CA_BUNDLE'),
        'cache_minutes' => (int) env('GEMINI_CACHE_MINUTES', 10),
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

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
        'app_name' => env('GOOGLE_DRIVE_APP_NAME', 'PO-Assist'),
        'scope' => env('GOOGLE_DRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file'),
    ],

];
