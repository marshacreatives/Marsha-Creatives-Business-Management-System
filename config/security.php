<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent API Key
    |--------------------------------------------------------------------------
    |
    | Shared secret used by the server agent to authenticate when posting
    | alerts, status, and block events to this dashboard. Must match the
    | agent's config.ini `agent_api_key` value.
    |
    */

    'agent_api_key' => env('AGENT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    |
    | Optional Telegram bot token and chat ID for real-time security alerts.
    |
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Default number of days to retain local security alert / server status
    | records before cleanup.
    |
    */

    'retention_days' => 30,
];
