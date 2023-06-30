<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\LaravelPulse\Traits\PulseRunnerTrait;
use Tkachikov\LaravelPulse\Services\ScheduleService;

class PulseFreeLogsCommand extends Command
{
    use PulseRunnerTrait;

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
