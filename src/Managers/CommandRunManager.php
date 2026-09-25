<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
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
        private readonly DatabaseHelper $databaseHelper,
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
    public function flush(): void
    {
        unset($this->lastRunForEachCommand);
    }

    #[\Override]
    public function getLastRunForEachCommand(): Collection
    {
        if (isset($this->lastRunForEachCommand)) {
            return $this->lastRunForEachCommand;
        }

        if (! $this->databaseHelper->hasTable(CommandRun::class)) {
            return collect();
        }

        $this->load();

        return $this->lastRunForEachCommand;
    }
}
