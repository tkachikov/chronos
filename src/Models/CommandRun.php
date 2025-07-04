<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandRun extends Model
{
    protected $fillable = [
        'command_id',
        'schedule_id',
        'telescope_id',
        'state',
        'memory',
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
}
