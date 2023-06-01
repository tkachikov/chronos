<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Tkachikov\LaravelPulse\CommandHandler;
use Tkachikov\LaravelPulse\Services\ScheduleService;

class PulseFreeLogsCommand extends CommandHandler
{
    protected $signature = 'pulse:free-logs {--hours=24}';

    protected $description = 'Free schedule logs';

    /**
     * @param ScheduleService $service
     *
     * @return int
     */
    public function handle(ScheduleService $service): int
    {
        foreach ($service->freeLogs() as $log) {
            $this->info($log);
        }

        return self::SUCCESS;
    }
}
