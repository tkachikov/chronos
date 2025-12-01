<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int|null $command_id
 * @property-read string|null $time_avg
 * @property-read string|null $time_min
 * @property-read string|null $time_max
 * @property-read string|null $memory_avg
 * @property-read string|null $memory_min
 * @property-read string|null $memory_max
 */
final class CommandMetric extends Model
{
    public static array $sortKeys = [
        'time_avg',
        'time_min',
        'time_max',
        'memory_avg',
        'memory_min',
        'memory_max',
    ];

    protected $guarded = [];
}
