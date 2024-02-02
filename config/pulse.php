<?php

declare(strict_types=1);

return [
    'domain' => env('PULSE_DOMAIN'),

    'middlewares' => [
        'web',
        'Tkachikov\LaravelPulse\Http\Middleware\Authorize',
        // 'auth',
    ],
];
