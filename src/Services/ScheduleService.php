<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Services;

use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;
use Tkachikov\LaravelPulse\Models\Command;
use Tkachikov\LaravelPulse\Models\Schedule;
use Tkachikov\LaravelPulse\Models\CommandLog;
use Tkachikov\LaravelPulse\Models\CommandRun;
use Tkachikov\LaravelPulse\Models\CommandMetric;
use Tkachikov\LaravelPulse\Helpers\DatabaseHelper;
use Tkachikov\LaravelPulse\Repositories\ScheduleRepository;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;

class ScheduleService
{
    public function __construct(
        private readonly CommandService     $commandService,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly DatabaseHelper     $databaseHelper,
    ) {
    }

    /**
     * @param ScheduleConsole $scheduleConsole
     *
     * @throws Exception
     *
     * @return void
     */
    public function schedule(ScheduleConsole $scheduleConsole): void
    {
        if (blank($this->commandService->get())) {
            return;
        }
        foreach ($this->scheduleRepository->get() as $schedule) {
            try {
                if (!class_exists($schedule->command->class)) {
                    continue;
                }
                $decorator = $this->commandService->get($schedule->command->class);
                if (!$decorator->runInSchedule()) {
                    continue;
                }
                $event = $scheduleConsole
                    ->command($schedule->command->class, $schedule->preparedArgs)
                    ->{$schedule->time_method}(...([$schedule->time_params] ?? []));
                $properties = [
                    'without_overlapping' => [$schedule->without_overlapping_time],
                    'run_in_background' => [],
                ];
                foreach ($properties as $property => $params) {
                    if (!$schedule->$property) {
                        continue;
                    }
                    $method = str($property)->camel()->toString();
                    $event->$method(...$params);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @param array  $input
     *
     * @return Schedule
     */
    public function saveSchedule(array $input): Schedule
    {
        return $this->scheduleRepository->save($input);
    }

    /**
     * @param string $class
     * @param string $message
     *
     * @return void
     */
    public function updateWaitingRun(string $class, string $message): void
    {
        $model = Command::firstWhere('class', $class);
        if (!$model) {
            return;
        }
        $run = CommandRun::query()
            ->whereCommandId($model->id)
            ->whereState(2)
            ->first();
        if (!$run) {
            return;
        }
        $run->update(['state' => 1]);
        CommandLog::create([
            'command_run_id' => $run->id,
            'type' => 'error',
            'message' => $message,
        ]);
    }

    /**
     * @param int $hours
     *
     * @return array
     */
    public function freeLogs(int $hours = 24): array
    {
        $logs = [];
        $date = now()->subHours($hours);
        $classes = [
            CommandRun::class,
            CommandLog::class,
        ];
        /** @var CommandRun|CommandLog $class */
        foreach ($classes as $class) {
            $query = $class::where('created_at', '<', $date);
            $basename = str($class)->classBasename()->toString();
            $logs[] = "Free $basename: {$query->count()}";
            $query->delete();
        }

        return $logs;
    }

    /**
     * @return void
     */
    public function updateMetrics(): void
    {
        $metrics = CommandMetric::query()
            ->get()
            ->keyBy('command_id')
            ->toArray();
        $newMetrics = $this->getStatistics();
        $rows = [];
        $now = now()->format('Y-m-d H:i:s');
        foreach ($newMetrics as $commandId => $values) {
            $row = ['command_id' => $commandId, 'created_at' => $now, 'updated_at' => $now];
            foreach (['time', 'memory'] as $type) {
                foreach (['avg', 'min', 'max'] as $key) {
                    $index = $type.'_'.$key;
                    $value = (float) $values[$index];
                    $oldValue = (float) ($metrics[$index] ?? 0);
                    $row[$index] = match ($key) {
                        'avg' => ($oldValue + $value) / ($oldValue ? 2 : 1),
                        'min' => $oldValue ? min($oldValue, $value) : $value,
                        'max' => $oldValue ? max($oldValue, $value) : $value,
                    };
                    if ($type === 'memory') {
                        $row[$index] .= ' MB';
                    }
                }
            }
            $rows[] = $row;
        }
        if ($rows) {
            CommandMetric::truncate();
            CommandMetric::insert($rows);
        }
    }

    /**
     * Values memory in Megabytes
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $diffDate = $this->databaseHelper->getTimeDiffInSeconds('created_at', 'updated_at');
        $concat = $this->databaseHelper->getConcat(DB::raw('round(avg(memory))'), ' MB');
        $select = [
            'command_id',
            DB::raw("round(avg($diffDate)) time_avg"),
            DB::raw("min($diffDate) time_min"),
            DB::raw("max($diffDate) time_max"),
            DB::raw("$concat memory_avg"),
            DB::raw('min(memory) memory_min'),
            DB::raw('max(memory) memory_max'),
        ];

        return CommandRun::query()
            ->select($select)
            ->groupBy('command_id')
            ->get()
            ->keyBy('command_id')
            ->toArray();
    }
}
