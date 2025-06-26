<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Models\Schedule;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosUpdateTimeParamsCommand extends Command
{
    protected $signature = 'chronos:update-time-params';

    protected $description = 'Update time params from method with single arg to methods with many args';

    public function handle(): int
    {
        Schedule::query()
            ->whereNotNull('time_params')
            ->eachById(function (Schedule $schedule) {
                if (is_string($schedule->time_params)) {
                    $schedule->time_params = [$schedule->time_params];
                    $schedule->save();
                }
            });

        return self::SUCCESS;
    }
}