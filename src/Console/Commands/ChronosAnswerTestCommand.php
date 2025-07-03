<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosAnswerTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:answer';

    protected $description = 'Test answer';

    public function handle(): int
    {
        $name = $this->ask('What is your name?');

        $this->info('Hello ' . $name);

        if ($this->confirm('Are you old 18 years?')) {
            $this->info('Has access');
        } else {
            $this->warn('Access denied');
        }

        return self::SUCCESS;
    }
}