<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosOnlyArgumentsTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:only-arguments {test1} {test2=test2} {test3?}';

    protected $description = 'Test arguments';

    public function handle(): void
    {
        $this->info('test1: ' . $this->argument('test1'));
        $this->info('test2: ' . $this->argument('test2'));
        $this->info('test3: ' . $this->argument('test3'));
    }
}
