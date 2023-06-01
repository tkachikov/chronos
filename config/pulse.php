<?php
declare(strict_types=1);

return [
    'domain' => env('PULSE_DOMAIN'),
    'user' => env('PULSE_USER', 'App\Models\User'),
];
