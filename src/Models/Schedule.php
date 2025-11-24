<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Tkachikov\LaravelWithtrashed\WithTrashedTrait;

/**
 * @property-read int|null $command_id
 * @property-read array|null $args
 * @property-read string|null $time_method
 * @property-read array|null $time_params
 * @property-read bool|null $without_overlapping
 * @property-read string|null $without_overlapping_time
 * @property-read bool|null $run_in_background
 * @property-read bool|null $run
 * @property-read int|null $user_id
 */
final class Schedule extends Model
{
    use WithTrashedTrait;

    protected $guarded = [];

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
                    if ($value === null) {
                        continue;
                    }

                    if (str($key)->startsWith('--')) {
                        if (is_bool($value)) {
                            $args[] = $key;
                        } else {
                            $args[$key] = $value;
                        }
                    } else {
                        $args[] = $value;
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
