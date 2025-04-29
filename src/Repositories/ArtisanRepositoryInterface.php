<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;

interface ArtisanRepositoryInterface
{
    /**
     * @return Collection<class-string, Command>
     */
    public function get(): Collection;
}