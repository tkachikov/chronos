<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\CommandRun;

interface CommandRunRepositoryInterface
{
    /**
     * @return Collection<int, CommandRun>
     */
    public function getLastRunForEachCommand(): Collection;
}
