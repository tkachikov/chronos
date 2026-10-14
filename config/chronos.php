<?php

declare(strict_types=1);

return [
    'domain' => env('CHRONOS_DOMAIN'),

    /*
     * System (not App\\) artisan commands that can be run and scheduled from Chronos,
     * by name or class, e.g. 'cache:clear', 'queue:restart'.
     * All system commands are disabled by default.
     */
    'system_commands' => [
        //
    ],

    'middlewares' => [
        'web',
        // 'auth',
        // 'Tkachikov\Chronos\Http\Middleware\Authorize',
    ],
];
