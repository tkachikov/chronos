<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Helpers\DatabaseHelper;

class ScheduleRepository
{
    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    public function get(): Collection
    {
        return $this->databaseHelper->hasTable(Schedule::class)
            ? Schedule::with('command')->whereRun(true)->get()
            : collect();
    }

    public function save(array $params): Schedule
    {
        $id = $params['id'] ?? null;
        $scheduleObject = new Schedule();
        $data = collect($params)->only($scheduleObject->getFillable())->toArray();
        $data['user_id'] = Auth::id();
        if ($id) {
            $schedule = Schedule::findOrFail($id);
            $schedule->update($data);
        } else {
            $schedule = Schedule::create($data);
        }

        return $schedule;
    }
}
