<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosMalformedTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:malformed';

    protected $description = 'Test Malformed UTF-8';

    public function handle(): int
    {
        $this->info(str_repeat(' ', CHRONOS_READ_BYTES - 1) . '€');

        return self::SUCCESS;
    }
}