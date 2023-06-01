<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Models;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\LaravelPulse\CommandHandler;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandRun extends Model
{
    protected $table = 'i_command_runs';

    protected $fillable = [
        'command_id',
        'schedule_id',
        'telescope_id',
        'state',
        'memory',
    ];

    /**
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CommandLog::class);
    }

    /**
     * @return string
     */
    public function getStateTitleAttribute(): string
    {
        return [
            'success',
            'failed',
            'waiting',
        ][$this->state ?? CommandHandler::WAITING];
    }

    /**
     * @return string
     */
    public function getStateCssAttribute(): string
    {
        return [
            'success',
            'danger',
            'warning'
        ][$this->state ?? CommandHandler::WAITING];
    }
}
