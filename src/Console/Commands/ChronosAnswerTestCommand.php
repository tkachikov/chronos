<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

class ChronosAnswerTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:answer';

    protected $description = 'Test answer';

    public function handle(): int
    {
        $name = $this->ask('What is your name?');

        $this->info('Hello ' . $name);

        return self::SUCCESS;
    }
}