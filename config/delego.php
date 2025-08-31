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

];
