<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Repositories;

use Tkachikov\LaravelPulse\Models\Schedule;
use Tkachikov\LaravelPulse\Helpers\DatabaseHelper;

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
            ? Schedule::with('command')->whereRun(true)->get()
            : collect();
    }
}
