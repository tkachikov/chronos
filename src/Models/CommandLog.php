<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Models;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\LaravelCommands\Enums\TypeMessageEnum;

class CommandLog extends Model
{
    protected $table = 'i_command_logs';

    protected $fillable = [
        'command_run_id',
        'type',
        'message',
    ];

    protected $casts = [
        'type' => TypeMessageEnum::class,
    ];
}
