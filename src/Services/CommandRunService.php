<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Managers\CommandRunManager;

final readonly class CommandRunService
{
    public function __construct(
        private CommandRunManager $commandRunManager,
    ) {}

    public function getLastRunForEachCommand(): Collection
    {
        return $this
            ->commandRunManager
            ->getLastRunForEachCommand();
    }
}