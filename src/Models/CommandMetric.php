<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;

class CommandMetric extends Model
{
    public static array $sortKeys = [
        'time_avg',
        'time_min',
        'time_max',
        'memory_avg',
        'memory_min',
        'memory_max',
    ];

    protected $fillable = [
        'class',
        'time_avg',
        'time_min',
        'time_max',
        'memory_avg',
        'memory_min',
        'memory_max',
    ];
}
