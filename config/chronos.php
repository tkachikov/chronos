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

    'middlewares' => [
        'web',
        // 'auth',
        // 'Tkachikov\Chronos\Http\Middleware\Authorize',
    ],
];
