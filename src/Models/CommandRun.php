<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int|null $command_id
 * @property-read int|null $state
 * @property-read string|null $memory
 * @property-read int|null $pid
 * @property-read int|null $user_id
 * @property-read class-string|null $user_type
 * @property-read array|null $args
 */
final class CommandRun extends Model
{
    protected $guarded = [];

    /**
     * @description For support version < 11
     */
    protected $casts = [
        'args' => 'array',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(CommandLog::class);
    }

    public function getStateTitleAttribute(): string
    {
        return [
            'success',
            'failed',
            'waiting',
            'killed',
        ][$this->state];
    }

    public function getStateCssAttribute(): string
    {
        return [
            'success',
            'danger',
            'warning',
            'danger',
        ][$this->state];
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'args' => 'array',
        ];
    }
}
