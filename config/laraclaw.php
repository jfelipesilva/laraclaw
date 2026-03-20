<?php

return [
    'claude_binary' => env('LARACLAW_CLAUDE_BINARY', 'claude'),
    'default_timeout' => env('LARACLAW_DEFAULT_TIMEOUT', 120),
    'default_max_turns' => env('LARACLAW_DEFAULT_MAX_TURNS', 10),
    'log_executions' => env('LARACLAW_LOG_EXECUTIONS', true),

    // Tokens de APIs externas
    'veico_plates_token' => env('LARACLAW_VEICO_PLATES_TOKEN'),

    // Google Calendar
    'google_calendar' => [
        'credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS', storage_path('app/google/credentials.json')),
        'token_path' => env('GOOGLE_CALENDAR_TOKEN', storage_path('app/google/token.json')),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        'sync_days_ahead' => env('GOOGLE_CALENDAR_DAYS_AHEAD', 7),
    ],

    // ClickUp
    'clickup' => [
        'token' => env('LARACLAW_CLICKUP_TOKEN'),
        'team_id' => env('LARACLAW_CLICKUP_TEAM_ID', '9013560078'),
        'list_id' => env('LARACLAW_CLICKUP_LIST_ID', '901320287156'),
        'sync_interval' => 5, // minutos
        'devs' => [
            ['name' => 'Filipe Sander',          'clickup_id' => 87901176],
            ['name' => 'Rafael',                  'clickup_id' => 170653166],
            ['name' => 'Guilherme Dias Tiede',    'clickup_id' => 3059278],
            ['name' => 'Bruno',                   'clickup_id' => 82198071],
        ],
    ],
];
