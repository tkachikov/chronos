<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Throwable;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Services\RealTime\RunnerService;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosRunBackgroundCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:run-background {command_id}';

    protected $description = 'Command for run other commands in background';

    /**
     * @throws Throwable
     */
    public function handle(RunnerService $service): int
    {
        $commandId = (int) $this->argument('command_id');
        $service->handle($commandId);

        return self::SUCCESS;
    }
}
