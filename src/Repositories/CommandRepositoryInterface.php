<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Tkachikov\Chronos\Models\Command;

interface CommandRepositoryInterface
{
    /**
     * @return Collection<int, Command>
     */
    public function get(): Collection;

    /**
     * @param class-string $class
     */
    public function getOrCreateByClass(string $class): Command;

    /**
     * @param class-string $class
     */
    public function getByClass(string $class): ?Command;

    /**
     * @param class-string $class
     */
    public function createByClass(string $class): Command;
}