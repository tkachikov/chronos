<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Services\ChronosRealTimeRunner;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;
use Tkachikov\Chronos\Models\Command as CommandModel;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosRunBackgroundCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:run-background {uuid}';

    protected $description = 'Command for run other commands in background';

    public function handle(ChronosRealTimeRunner $service): int
    {
        $service->run($this->argument('uuid'));

        return self::SUCCESS;
    }
}