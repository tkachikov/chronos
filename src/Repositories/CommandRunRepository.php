<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Models\CommandRun;

final readonly class CommandRunRepository implements CommandRunRepositoryInterface
{
    #[\Override]
    public function getLastRunForEachCommand(): Collection
    {
        $subQuery = CommandRun::query()
            ->select([
                DB::raw('max(id) as id'),
                'command_id',
            ])
            ->groupByRaw('2');

        /** @var Collection<int, CommandRun> $commandRuns */
        $commandRuns = CommandRun::query()
            ->select('command_runs.*')
            ->joinSub($subQuery, 'sub', 'sub.id', 'command_runs.id')
            ->get();

        return $commandRuns;
    }
}
