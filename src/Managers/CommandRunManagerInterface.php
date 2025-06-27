<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\CommandRun;

interface CommandRunManagerInterface
{
    /**
     * @return Collection<int, CommandRun>
     */
    public function getLastRunForEachCommand(): Collection;
}