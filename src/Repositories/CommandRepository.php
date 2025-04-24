<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\Command;

final readonly class CommandRepository
{
    /**
     * @return Collection<int, Command>
     */
    public function get(): Collection
    {
        return Command::get();
    }

    public function getOrCreateByClass(string $class): Command
    {
        return $this->getByClass($class) ?? $this->createByClass($class);
    }

    public function getByClass(string $class): ?Command
    {
        return $this
            ->get()
            ->where('class', $class)
            ->first();
    }

    public function createByClass(string $class): Command
    {
        return Command::firstOrCreate(['class' => $class]);
    }
}