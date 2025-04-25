<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Tkachikov\LaravelWithtrashed\WithTrashedTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use WithTrashedTrait;

    protected $fillable = [
        'command_id',
        'args',
        'time_method',
        'time_params',
        'without_overlapping',
        'without_overlapping_time',
        'run_in_background',
        'run',
        'user_id',
    ];

    protected $casts = [
        'args' => 'array',
        'time_params' => 'array',
    ];

    public function preparedArgs(): Attribute
    {
        return Attribute::make(
            get: function () {
                $args = [];
                foreach ($this->args as $key => $value) {
                    if (!str($key)->startsWith('--') || !is_bool($value)) {
                        $args[$key] = $value;
                    } elseif ($value) {
                        $args[] = $key;
                    }
                }

                return $args;
            },
        );
    }

    public function args(): Attribute
    {
        return Attribute::make(
            get: function ($values) {
                $args = [];

                foreach (json_decode($values, true) as $item) {
                    $args[$item['key']] = $item['value'];
                }

                return $args;
            },
        );
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CommandRun::class, 'class', 'class');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Auth::user()::class);
    }
}
