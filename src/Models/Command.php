<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read class-string|null $class
 */
final class Command extends Model
{
    protected $guarded = [];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CommandRun::class);
    }

    public function metrics(): HasOne
    {
        return $this->hasOne(CommandMetric::class);
    }

    public function lastRun(): HasOne
    {
        return $this
            ->hasOne(CommandRun::class)
            ->latest('id');
    }
}
