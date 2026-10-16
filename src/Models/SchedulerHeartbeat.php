<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property-read string $host
 * @property-read Carbon $last_run_at
 */
final class SchedulerHeartbeat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_run_at' => 'datetime',
    ];
}
