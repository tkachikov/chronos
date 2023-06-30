<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Tkachikov\LaravelPulse\Models\Schedule;
use Tkachikov\LaravelPulse\Helpers\DatabaseHelper;

class ScheduleRepository
{
    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->databaseHelper->hasConnect()
            && $this->databaseHelper->hasTable(Schedule::class)
            ? Schedule::with('command')->whereRun(true)->get()
            : collect();
    }

    /**
     * @param array $params
     *
     * @return Schedule
     */
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
