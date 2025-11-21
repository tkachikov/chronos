<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\Command;

final readonly class CommandRepository implements CommandRepositoryInterface
{
    private Collection $commands;

    #[\Override]
    public function get(): Collection
    {
        return $this->commands ??= Command::get()->keyBy('class');
    }

    #[\Override]
    public function getOrCreateByClass(string $class): Command
    {
        return $this->getByClass($class)
            ?? $this->createByClass($class);
    }

    #[\Override]
    public function getByClass(string $class): ?Command
    {
        return $this
            ->get()
            ->get($class);
    }

    #[\Override]
    public function createByClass(string $class): Command
    {
        $command = Command::firstOrCreate(['class' => $class]);

        if ($command->wasRecentlyCreated) {
            $this->commands->push($command);
        }

        return $command;
    }
}
