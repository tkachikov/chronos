<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends Model
{
    protected $table = 'i_schedules';

    protected $fillable = [
        'command_id',
        'args',
        'time_method',
        'time_params',
        'without_overlapping',
        'run_in_background',
        'run',
        'user_id',
    ];

    protected $casts = [
        'args' => 'array',
        'time_params' => 'array',
    ];

    /**
     * @return Attribute
     */
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

    /**
     * @return BelongsTo
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    /**
     * @return HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(CommandRun::class, 'class', 'class');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
