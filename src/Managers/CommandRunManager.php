<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Repositories\CommandRunRepositoryInterface;

final class CommandRunManager implements CommandRunManagerInterface
{
    /**
     * @var Collection<int, CommandRun> $lastRunForEachCommand
     */
    private Collection $lastRunForEachCommand;

    public function __construct(
        private readonly CommandRunRepositoryInterface $commandRunRepository,
    ) {
    }

    #[\Override]
    public function load(): void
    {
        $this->lastRunForEachCommand = $this
            ->commandRunRepository
            ->getLastRunForEachCommand()
            ->keyBy('command_id');
    }

    #[\Override]
    public function getLastRunForEachCommand(): Collection
    {
        return $this->lastRunForEachCommand;
    }
}
