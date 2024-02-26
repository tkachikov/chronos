<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Enums\TypeMessageEnum;

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
