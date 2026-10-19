<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Throwable;
use Tkachikov\Chronos\Helpers\MemoryHelper;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandMetric;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ScheduleRepository;
use Tkachikov\Chronos\Repositories\TimeRepositoryInterface;

class ScheduleService
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly TimeRepositoryInterface $timeRepository,
        private readonly MemoryHelper $memoryHelper,
        private readonly ScheduleTrackerService $scheduleTracker,
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

                $this->register($scheduleConsole, $schedule);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    public function register(ScheduleConsole $scheduleConsole, Schedule $schedule): Event
    {
        $args = $schedule->time_params ?? [];
        $event = $scheduleConsole
            ->command($schedule->command->class, $schedule->preparedArgs)
            ->{$schedule->time_method}(...$args);

        if ($schedule->name) {
            $event->description($schedule->name);
        }

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

        $this->scheduleTracker->track($event, $schedule);

        return $event;
    }

    public function saveSchedule(array $input): void
    {
        $input['time_params'] ??= null;

        $timeMethod = $input['time_method'];
        $time = $this
            ->timeRepository
            ->get()[$timeMethod];

        if (!$time->params) {
            $input['time_params'] = null;
        } else {
            $params = $input['time_params'];
            $input['time_params'] = [];

            foreach ($time->params as $key => $param) {
                $input['time_params'][$key] = $params[$key]
                    ?? $param->default;
            }
        }

        $this
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

    /**
     * Avg is calculated by the runs that are stored now (they are deleted by chronos:free-logs),
     * min and max are kept for all time.
     */
    public function updateMetrics(): void
    {
        $metrics = CommandMetric::query()
            ->get()
            ->keyBy('command_id');

        foreach ($this->getStatistics() as $commandId => $values) {
            /** @var CommandMetric|null $metric */
            $metric = $metrics->get($commandId);

            $timeMin = min($values['time_min'], (float) ($metric->time_min ?? INF));
            $timeMax = max($values['time_max'], (float) ($metric->time_max ?? -INF));
            $row = [
                'time_avg' => (string) round($values['time_avg']),
                'time_min' => (string) round($timeMin),
                'time_max' => (string) round($timeMax),
            ];

            if ($values['memory_avg'] === null) {
                $row += [
                    'memory_avg' => $metric->memory_avg ?? '',
                    'memory_min' => $metric->memory_min ?? '',
                    'memory_max' => $metric->memory_max ?? '',
                ];
            } else {
                $oldMin = $this->memoryHelper->toBytes($metric?->memory_min);
                $oldMax = $this->memoryHelper->toBytes($metric?->memory_max);

                $row += [
                    'memory_avg' => $this->memoryHelper->format($values['memory_avg']),
                    'memory_min' => $this->memoryHelper->format(min($values['memory_min'], $oldMin ?? INF)),
                    'memory_max' => $this->memoryHelper->format(max($values['memory_max'], $oldMax ?? -INF)),
                ];
            }

            CommandMetric::query()->updateOrCreate(
                ['command_id' => $commandId],
                $row,
            );
        }
    }

    /**
     * Statistics of finished runs: time in seconds, memory in bytes.
     *
     * @return array<int, array{time_avg: float, time_min: float, time_max: float, memory_avg: float|null, memory_min: float|null, memory_max: float|null}>
     */
    public function getStatistics(): array
    {
        $runs = [];

        CommandRun::query()
            ->select(['id', 'command_id', 'memory', 'created_at', 'updated_at'])
            ->where('state', '!=', 2)
            ->lazyById()
            ->each(function (CommandRun $run) use (&$runs): void {
                $commandId = (int) $run->command_id;

                $runs[$commandId]['time'][] = abs($run->updated_at->getTimestamp() - $run->created_at->getTimestamp());

                $memory = $this->memoryHelper->toBytes($run->memory);

                if ($memory !== null) {
                    $runs[$commandId]['memory'][] = $memory;
                }
            });

        return array_map(fn (array $values): array => [
            'time_avg' => array_sum($values['time']) / count($values['time']),
            'time_min' => (float) min($values['time']),
            'time_max' => (float) max($values['time']),
            'memory_avg' => isset($values['memory']) ? array_sum($values['memory']) / count($values['memory']) : null,
            'memory_min' => isset($values['memory']) ? min($values['memory']) : null,
            'memory_max' => isset($values['memory']) ? max($values['memory']) : null,
        ], $runs);
    }
}
