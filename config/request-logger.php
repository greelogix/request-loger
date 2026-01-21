<?php

return [
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),

    'driver' => env('REQUEST_LOGGER_DRIVER', 'database'), // 'database' or 'file'

    'table' => 'request_logs',

    'file_channel' => env('REQUEST_LOGGER_CHANNEL', env('LOG_CHANNEL', 'stack')),

    'masked_keys' => [
        'password',
        'password_confirmation',
        'authorization',
        'token',
        'api_key',
        'apikey',
        'secret',
        'session',
        'cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Routes (Path/URI Patterns)
    |--------------------------------------------------------------------------
    |
    | Patterns to exclude from logging. Uses Laravel's Str::is() matching.
    | Supports wildcards: 'admin/*', 'api/users*', etc.
    |
    */
    'ignored_routes' => [
        'request-logs*',
        'request-logs-check-new',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored URLs (Full URL Patterns)
    |--------------------------------------------------------------------------
    |
    | Full URL patterns to exclude from logging. Supports wildcards and regex.
    | Examples: 'https://example.com/api/*' or regex patterns.
    |
    */
    'ignored_urls' => [
        // 'https://example.com/webhook*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths (Regex Patterns)
    |--------------------------------------------------------------------------
    |
    | Regular expression patterns for path/URI matching. Must be valid regex.
    | Examples: '/^\/api\/v\d+\/.*$/', '/^\/admin\/.*$/'
    |
    */
    'ignored_paths_regex' => [
        // '/^\/api\/v\d+\/health$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slow Request Threshold
    |--------------------------------------------------------------------------
    |
    | Requests taking longer than this duration (in milliseconds) will be
    | marked as "slow" in the log viewer.
    |
    */
    'slow_request_threshold_ms' => env('REQUEST_LOGGER_SLOW_THRESHOLD', 1000),

    /*
    |--------------------------------------------------------------------------
    | Log HTML Responses
    |--------------------------------------------------------------------------
    |
    | If set to false, HTML responses will be replaced with "HTML response"
    | text instead of logging the actual HTML content. This helps reduce
    | database size and improve performance.
    |
    */
    'log_html_responses' => env('REQUEST_LOGGER_LOG_HTML', false),
];
