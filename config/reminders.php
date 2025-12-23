<?php

return [
    'lead_time_days' => env('REMINDER_LEAD_TIME_DAYS', 7),
    'send_time' => env('REMINDER_SEND_TIME', '09:00'),
    'christmas_default_date' => env('CHRISTMAS_DEFAULT_DATE', '12-25'),
    'default_channels' => ['mail'],
    'channels' => [
        'mail' => [
            'enabled' => env('REMINDER_MAIL_ENABLED', true),
        ],
        'slack' => [
            'webhook' => env('REMINDER_SLACK_WEBHOOK'),
        ],
        'discord' => [
            'webhook' => env('REMINDER_DISCORD_WEBHOOK'),
        ],
        'push' => [
            'endpoint' => env('REMINDER_PUSH_ENDPOINT'),
            'token' => env('REMINDER_PUSH_TOKEN'),
        ],
    ],
];
