<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Services\CommandRunService;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;
use Illuminate\Support\Facades\Log;
use Tkachikov\Chronos\Models\Command as CommandModel;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosRunBackgroundCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:run-background {uuid}';

    protected $description = 'Command for run other commands in background';

    public function handle(CommandRunService $service): int
    {
        $data = cache()->get($this->argument('uuid'));
        $command = CommandModel::findOrFail($data['command_id']);
        $service->run($command, $data['uuid'], $data['args']);

        return self::SUCCESS;
    }
}