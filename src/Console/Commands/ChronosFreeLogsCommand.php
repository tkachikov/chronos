<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;
use Tkachikov\Chronos\Services\ScheduleService;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosFreeLogsCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:free-logs {--hours=24}';

    protected $description = 'Free schedule logs';

    public function handle(ScheduleService $service): int
    {
        foreach ($service->freeLogs() as $log) {
            $this->info($log);
        }

        return self::SUCCESS;
    }
}
