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
    'router' => [
        0 => [
            'host'  => env('ROUTER_HOST', '192.168.1.1'),
            'user'  => env('ROUTER_USER', 'admin'),
            'pass'  => env('ROUTER_PASSWORD', 'admin'),
            'port'  => env('ROUTER_PORT', '8728'),
            'name'  => env('ROUTER_NAME', 'Router 1'),
            'id'    => 0,
        ],
        1 => [
            'host'  => '192.168.1.1',
            'user'  => 'admin',
            'pass'  => 'admin',
            'port'  => '8728',
            'name'  => 'Router 2',
            'id'    => 1,
        ],
        2 => [
            'host'  => '192.168.1.1',
            'user'  => 'admin',
            'pass'  => 'admin',
            'port'  => '8728',
            'name'  => 'Router 2',
            'id'    => 2,
        ],
    ],

];
