<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Actions\InitializeCacheAction;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\CommandMetric;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Repositories\ScheduleRepository;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Tkachikov\Chronos\Repositories\TimeRepositoryInterface;

class ScheduleService
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly DatabaseHelper $databaseHelper,
        private readonly TimeRepositoryInterface $timeRepository,
        private readonly InitializeCacheAction $initializeCacheAction,
    ) {
    }

    /**
     * @throws Exception
     */
    public function schedule(ScheduleConsole $scheduleConsole): void
    {
        if (app()->isDownForMaintenance()) {
            return;
        }

        foreach ($this->scheduleRepository->get() as $schedule) {
            try {
                if (!class_exists($schedule->command->class)) {
                    continue;
                }

                if (!$this->commandService->exists($schedule->command->class)) {
                    continue;
                }

                $decorator = $this->commandService->getByClass($schedule->command->class);

                if (!$decorator->runInSchedule()) {
                    continue;
                }

                $runDto = new RunDto(
                    commandId: $schedule->command_id,
                    args: $schedule->args,
                );

                $this
                    ->initializeCacheAction
                    ->execute($runDto);

                $args = $schedule->time_params ?? [];
                $event = $scheduleConsole
                    ->command($schedule->command->class, $schedule->preparedArgs)
                    ->{$schedule->time_method}(...$args);
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

    public function saveSchedule(array $input): Schedule
    {
        $input['time_params'] ??= null;

        $timeMethod = $input['time_method'];
        $time = $this
            ->timeRepository
            ->get()[$timeMethod];

        if (!$time->params) {
            $input['time_params'] = null;
        } else {
            $input['time_params'] = array_slice(
                $input['time_params'],
                0,
                count($time->params),
            );
        }

        return $this
            ->scheduleRepository
            ->save($input);
    }

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
     * @description Values memory in Megabytes
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
