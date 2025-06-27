<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Command extends Model
{
    use HasFactory;

    protected $fillable = [
        'class',
    ];

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
