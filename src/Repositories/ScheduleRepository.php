<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Schedule;

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

    public function save(array $params): void
    {
        if ($params['args']) {
            $newArgs = [];
            foreach ($params['args'] as $key => $value) {
                $newArgs[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
            $params['args'] = $newArgs;
        }

        $id = data_get($params, 'id');
        $user = Auth::user();

        $data = [
            'command_id' => data_get($params, 'command_id'),
            'args' => data_get($params, 'args'),
            'time_method' => data_get($params, 'time_method'),
            'time_params' => data_get($params, 'time_params'),
            'without_overlapping' => data_get($params, 'without_overlapping'),
            'without_overlapping_time' => data_get($params, 'without_overlapping_time'),
            'run_in_background' => data_get($params, 'run_in_background'),
            'run' => data_get($params, 'run'),
            'user_id' => $user?->getAuthIdentifier(),
        ];

        if ($this->databaseHelper->hasColumn(Schedule::class, 'user_type')) {
            $data['user_type'] = $user instanceof Model
                ? $user->getMorphClass()
                : null;
        }

        if ($id) {
            $schedule = Schedule::findOrFail($id);
            $schedule->update($data);
        } else {
            Schedule::create($data);
        }
    }
}
