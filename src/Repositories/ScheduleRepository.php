<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Repositories;

use Tkachikov\LaravelCommands\Models\Schedule;
use Tkachikov\LaravelCommands\Helpers\DatabaseHelper;

class ScheduleRepository
{
    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    public function get()
    {
        return $this->databaseHelper->hasConnect()
            && $this->databaseHelper->hasTable(Schedule::class)
            ? Schedule::whereRun(true)->get()
            : collect();
    }
}
