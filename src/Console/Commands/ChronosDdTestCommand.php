<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use JetBrains\PhpStorm\NoReturn;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosDdTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:dd';

    protected $description = 'Test dd';

    #[NoReturn]
    public function handle(): void
    {
        $this->dd(
            (object) [
                'test' => 'Test',
                'test2' => 123,
            ],
            "it's work!",
        );
    }
}