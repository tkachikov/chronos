<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Enums\TypeMessageEnum;

/**
 * @property-read int|null $command_run_id
 * @property-read TypeMessageEnum|null $type
 * @property-read string|null $message
 */
final class CommandLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => TypeMessageEnum::class,
    ];
}
