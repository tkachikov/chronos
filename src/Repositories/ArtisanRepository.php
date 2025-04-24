<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

final readonly class ArtisanRepository
{
    public function get(): Collection
    {
        return collect(Artisan::all())
            ->keyBy(fn(Command $command) => $command::class);
    }
}