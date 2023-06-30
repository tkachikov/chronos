<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Models;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\LaravelPulse\Enums\TypeMessageEnum;

class CommandLog extends Model
{
    protected $fillable = [
        'command_run_id',
        'type',
        'message',
    ];

    protected $casts = [
        'type' => TypeMessageEnum::class,
    ];
}
