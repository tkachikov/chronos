<?php

declare(strict_types=1);

return [
    'domain' => env('CHRONOS_DOMAIN'),

    'middlewares' => [
        'web',
        'Tkachikov\Chronos\Http\Middleware\Authorize',
        // 'auth',
    ],
];
