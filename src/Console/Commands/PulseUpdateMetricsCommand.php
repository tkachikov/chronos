<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\LaravelPulse\Traits\PulseRunnerTrait;
use Tkachikov\LaravelPulse\Services\ScheduleService;

class PulseUpdateMetricsCommand extends Command
{
    use PulseRunnerTrait;

    protected $signature = 'pulse:update-metrics';

    protected $description = 'Update metrics for run commands';

    /**
     * @param ScheduleService $service
     *
     * @return int
     */
    public function handle(ScheduleService $service): int
    {
        $service->updateMetrics();

        return self::SUCCESS;
    }
}
