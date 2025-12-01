<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Models\CommandRun;

interface CommandRunManagerInterface
{
    public function load(): void;

    /**
     * @return Collection<int, CommandRun>
     */
    public function getLastRunForEachCommand(): Collection;
}
