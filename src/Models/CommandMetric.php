<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Models;

use Illuminate\Database\Eloquent\Model;

class CommandMetric extends Model
{
    protected $table = 'i_command_metrics';

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
