<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Repositories\CommandRunRepositoryInterface;

final readonly class CommandRunManager
{
    /**
     * @var Collection<int, CommandRun> $lastRunForEachCommand
     */
    private Collection $lastRunForEachCommand;

    public function __construct(
        private CommandRunRepositoryInterface $commandRunRepository,
    ) {}

    public function getLastRunForEachCommand(): Collection
    {
        return $this->lastRunForEachCommand ??= $this
            ->commandRunRepository
            ->getLastRunForEachCommand()
            ->keyBy('command_id');
    }
}