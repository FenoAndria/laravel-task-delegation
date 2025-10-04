<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the login page offers one-click access to the demo personas
    | and the database can be reset with `php artisan demo:reset`.
    |
    */

    'demo' => (bool) env('DELEGO_DEMO', false),

    /*
    |--------------------------------------------------------------------------
    | Workload
    |--------------------------------------------------------------------------
    |
    | Open work is measured in estimated hours. Tasks without an estimate count
    | for a default amount, and a person's load is compared with a weekly
    | capacity to tell who is available, balanced or overloaded.
    |
    */

    'workload' => [
        'weekly_capacity_hours' => (int) env('DELEGO_WEEKLY_CAPACITY_HOURS', 30),
        'default_estimate_hours' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Risks
    |--------------------------------------------------------------------------
    */

    'risks' => [
        'due_soon_days' => 3,
        'blocked_for_days' => 2,
        'unassigned_due_within_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI assistant (optional)
    |--------------------------------------------------------------------------
    |
    | Any OpenAI-compatible chat completions API works, including providers
    | with a free tier. Without an API key the assistant is simply hidden and
    | everything else keeps working.
    |
    */

    'ai' => [
        'base_url' => rtrim((string) env('DELEGO_AI_BASE_URL', 'https://openrouter.ai/api/v1'), '/'),
        'api_key' => env('DELEGO_AI_API_KEY'),
        'model' => env('DELEGO_AI_MODEL'),
        'timeout' => (int) env('DELEGO_AI_TIMEOUT', 30),
        'max_per_hour' => (int) env('DELEGO_AI_MAX_PER_HOUR', 10),
    ],

];
