<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

final class ArtisanRepository implements ArtisanRepositoryInterface
{
    private Collection $commands;

    public function load(): void
    {
        $this->commands = collect(Artisan::all())
            ->keyBy(fn (Command $command) => $command::class);
    }

    #[\Override]
    public function get(): Collection
    {
        return $this->commands;
    }
}
