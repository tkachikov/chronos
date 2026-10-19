<?php

declare(strict_types=1);

return [
    'domain' => env('CHRONOS_DOMAIN'),

    /*
     * System (not App\) artisan commands that can be run and scheduled from Chronos,
     * by name or class, e.g. 'cache:clear', 'queue:restart'.
     * All system commands are disabled by default.
     */
    'system_commands' => [
        //
    ],

    /*
     * Commands for testing Chronos (chronos:test, chronos:answer, chronos:dd, ...).
     * By default they are registered only in the local environment.
     */
    'test_commands' => env('CHRONOS_TEST_COMMANDS'),

    /*
     * PHP binary for running commands in real time.
     * By default it is found automatically (PHP_BINARY in console, PHP_BINDIR or PATH under php-fpm).
     */
    'php_binary' => env('CHRONOS_PHP_BINARY'),

    'monitoring' => [
        /*
         * The scheduler is considered stopped when no server has run schedule:run for this number of minutes.
         */
        'scheduler_timeout' => (int) env('CHRONOS_SCHEDULER_TIMEOUT', 5),

        /*
         * GET /chronos/health returns 503 when the scheduler is stopped or schedules have missed runs.
         * Without a token it is behind the middlewares of the panel,
         * with a token it is available by `Authorization: Bearer <token>` or `?token=<token>` only.
         */
        'health_token' => env('CHRONOS_HEALTH_TOKEN'),

        /*
         * GET /chronos/dashboard shows runs of commands, errors and the scheduler in real time, e.g. on a monitor.
         * Without a token it is behind the middlewares of the panel,
         * with a token it is available by `?token=<token>` only.
         */
        'dashboard_token' => env('CHRONOS_DASHBOARD_TOKEN'),
    ],

    'middlewares' => [
        'web',
        // 'auth',
        // 'Tkachikov\Chronos\Http\Middleware\Authorize',
    ],
];
