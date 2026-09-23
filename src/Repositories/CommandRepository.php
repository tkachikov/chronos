<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Command;

final class CommandRepository implements CommandRepositoryInterface
{
    /**
     * @var Collection<string, Command> $commands
     */
    private Collection $commands;

    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    #[\Override]
    public function load(): void
    {
        $this->commands = Command::query()
            ->get()
            ->keyBy('class');
    }

    #[\Override]
    public function get(): Collection
    {
        if (isset($this->commands)) {
            return $this->commands;
        }

        if (! $this->databaseHelper->hasTable(Command::class)) {
            return collect();
        }

        $this->load();

        return $this->commands;
    }

    #[\Override]
    public function getOrCreateByClass(string $class): Command
    {
        return $this->getByClass($class)
            ?? $this->createByClass($class);
    }

    private function getByClass(string $class): ?Command
    {
        return $this
            ->get()
            ->get($class);
    }

    private function createByClass(string $class): Command
    {
        $command = Command::firstOrCreate(['class' => $class]);

        $this
            ->get()
            ->put($class, $command);

        return $command;
    }
}
