<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Models\Command;

interface CommandRepositoryInterface
{
    public function load(): void;

    /**
     * @return Collection<string, Command>
     */
    public function get(): Collection;

    /**
     * @param class-string $class
     */
    public function getOrCreateByClass(string $class): Command;
}
