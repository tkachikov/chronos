<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;
use Tkachikov\Chronos\Services\ScheduleService;

class ChronosUpdateMetricsCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:update-metrics';

    protected $description = 'Update metrics for run commands';

    public function handle(ScheduleService $service): int
    {
        $service->updateMetrics();

        return self::SUCCESS;
    }
}
