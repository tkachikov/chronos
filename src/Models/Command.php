<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Command extends Model
{
    use HasFactory;

    protected $table = 'i_commands';

    protected $fillable = [
        'class',
    ];

    /**
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(CommandRun::class);
    }

    /**
     * @return HasOne
     */
    public function metrics(): HasOne
    {
        return $this->hasOne(CommandMetric::class);
    }
}
