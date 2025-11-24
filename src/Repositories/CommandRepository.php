<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Models\Command;

final class CommandRepository implements CommandRepositoryInterface
{
    private Collection $commands;

    public function load(): void
    {
        $this->commands = Command::query()
            ->get()
            ->keyBy('class');
    }

    #[\Override]
    public function get(): Collection
    {
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

        if ($command->wasRecentlyCreated) {
            $this->commands->push($command);
        }

        return $command;
    }
}
