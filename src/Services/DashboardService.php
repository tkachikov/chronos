<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Tkachikov\Chronos\Enums\ScheduleStatusEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Helpers\MemoryHelper;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Repositories\ScheduleRepository;

/**
 * Data of the real-time dashboard: runs of commands, errors and health of the scheduler.
 */
class DashboardService
{
    public const HOURS = 24;

    public const LIMIT = 20;

    private const STATES = [
        0 => 'success',
        1 => 'failed',
        2 => 'running',
        3 => 'killed',
    ];

    /**
     * @var array<string, string>|null
     */
    private ?array $names = null;

    public function __construct(
        private readonly HealthService $healthService,
        private readonly ScheduleMonitorService $scheduleMonitor,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly ArtisanRepositoryInterface $artisanRepository,
        private readonly DatabaseHelper $databaseHelper,
        private readonly MemoryHelper $memoryHelper,
    ) {
    }

    public function get(): array
    {
        $health = $this->healthService->check();
        $hasRuns = $this->databaseHelper->hasTable(CommandRun::class);

        return [
            'generated_at' => now()->toIso8601String(),
            'status' => $health['status'],
            'scheduler' => $health['scheduler'],
            'schedules' => [
                'enabled' => $health['schedules']['enabled'],
                'missed' => array_map(
                    fn (array $missed): array => ['command' => $this->getName($missed['command'])] + $missed,
                    $health['schedules']['missed'],
                ),
                'stuck' => array_map(
                    fn (array $stuck): array => ['command' => $this->getName($stuck['command'])] + $stuck,
                    $health['schedules']['stuck'],
                ),
                'failed' => $this->getFailedSchedules(),
                'upcoming' => $this->getUpcomingSchedules(),
            ],
            'runs' => $hasRuns
                ? $this->getRuns()
                : $this->getEmptyRuns(),
        ];
    }

    private function getRuns(): array
    {
        [$totals, $hourly] = $this->getStatistics();

        return [
            'hours' => self::HOURS,
            'totals' => $totals,
            'hourly' => $hourly,
            'duration' => rescue(fn () => $this->getDuration(), null, false),
            'memory' => $this->getMemory(),
            'running' => $this->mapRuns(
                CommandRun::query()
                    ->with('command')
                    ->where('state', 2)
                    ->latest('id')
                    ->limit(self::LIMIT)
                    ->get(),
            ),
            'errors' => $this->getErrors(),
            'recent' => $this->mapRuns(
                CommandRun::query()
                    ->with('command')
                    ->whereIn('state', [0, 1, 3])
                    ->latest('id')
                    ->limit(self::LIMIT)
                    ->get(),
            ),
        ];
    }

    /**
     * Counts by states for the whole period and by hours in one query, portable between databases.
     *
     * @return array{0: array<string, int>, 1: list<array{hour: string, success: int, failed: int}>}
     */
    private function getStatistics(): array
    {
        $start = $this->getStart();
        $selects = [];
        $bindings = [];

        foreach (self::STATES as $state => $title) {
            $selects[] = "sum(case when state = $state then 1 else 0 end) as total_$title";
        }

        for ($i = 0; $i < self::HOURS; $i++) {
            $from = $start->copy()->addHours($i)->format('Y-m-d H:i:s');
            $to = $start->copy()->addHours($i + 1)->format('Y-m-d H:i:s');

            $selects[] = "sum(case when created_at >= ? and created_at < ? and state = 0 then 1 else 0 end) as success_$i";
            $selects[] = "sum(case when created_at >= ? and created_at < ? and state in (1, 3) then 1 else 0 end) as failed_$i";
            array_push($bindings, $from, $to, $from, $to);
        }

        $row = (array) CommandRun::query()
            ->toBase()
            ->selectRaw(implode(', ', $selects), $bindings)
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->first();

        $totals = ['total' => 0];

        foreach (self::STATES as $title) {
            $totals[$title] = (int) ($row["total_$title"] ?? 0);
            $totals['total'] += $totals[$title];
        }

        $hourly = [];

        for ($i = 0; $i < self::HOURS; $i++) {
            $hourly[] = [
                'hour' => $start->copy()->addHours($i)->toIso8601String(),
                'success' => (int) ($row["success_$i"] ?? 0),
                'failed' => (int) ($row["failed_$i"] ?? 0),
            ];
        }

        return [$totals, $hourly];
    }

    /**
     * Failed and killed runs of the period grouped by commands, the last failed first.
     */
    private function getErrors(): array
    {
        $groups = CommandRun::query()
            ->toBase()
            ->selectRaw(implode(', ', [
                'command_id',
                'count(*) as total',
                'sum(case when state = 1 then 1 else 0 end) as failed',
                'sum(case when state = 3 then 1 else 0 end) as killed',
                'max(id) as last_id',
            ]))
            ->whereIn('state', [1, 3])
            ->where('created_at', '>=', $this->getStart()->format('Y-m-d H:i:s'))
            ->groupBy('command_id')
            ->orderByDesc('last_id')
            ->limit(self::LIMIT)
            ->get();

        $lastRuns = CommandRun::query()
            ->with('command')
            ->whereIn('id', $groups->pluck('last_id'))
            ->get()
            ->keyBy('id');

        return $groups
            ->map(function (object $group) use ($lastRuns): ?array {
                $lastRun = $lastRuns->get((int) $group->last_id);

                if (!$lastRun instanceof CommandRun) {
                    return null;
                }

                return [
                    'command' => $this->getName($lastRun->command?->class),
                    'class' => $lastRun->command?->class,
                    'total' => (int) $group->total,
                    'failed' => (int) $group->failed,
                    'killed' => (int) $group->killed,
                    'last' => $this->mapRun($lastRun),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Time of finished runs of the period in seconds: the average and the slowest run.
     * Without killed runs: the time of a run killed by SIGKILL is known only up to its detection.
     * Null for a database whose time functions are unknown.
     */
    private function getDuration(): ?array
    {
        $diff = $this->databaseHelper->getTimeDiffInSeconds('created_at', 'updated_at');
        $query = fn () => CommandRun::query()
            ->whereIn('state', [0, 1])
            ->where('created_at', '>=', $this->getStart()->format('Y-m-d H:i:s'));

        $row = $query()
            ->toBase()
            ->selectRaw("count(*) as total, avg($diff) as average")
            ->first();

        if (!$row || !(int) $row->total) {
            return null;
        }

        /** @var CommandRun|null $slowest */
        $slowest = $query()
            ->with('command')
            ->orderByRaw("$diff desc")
            ->orderByDesc('id')
            ->first();

        return [
            'average' => round((float) $row->average, 1),
            'max' => $slowest ? $this->mapRun($slowest)['duration'] : null,
            'max_command' => $slowest ? $this->getName($slowest->command?->class) : null,
        ];
    }

    /**
     * Peak memory of finished runs of the period: the average and the largest run.
     * Memory is stored as a string with units, so runs are grouped by its values: there are few of them.
     */
    private function getMemory(): ?array
    {
        $groups = CommandRun::query()
            ->toBase()
            ->selectRaw('memory, count(*) as total, max(id) as last_id')
            ->whereIn('state', [0, 1, 3])
            ->whereNotNull('memory')
            ->where('created_at', '>=', $this->getStart()->format('Y-m-d H:i:s'))
            ->groupBy('memory')
            ->get();

        $count = 0;
        $sum = 0.0;
        $max = null;

        foreach ($groups as $group) {
            $bytes = $this->memoryHelper->toBytes((string) $group->memory);

            if ($bytes === null) {
                continue;
            }

            $count += (int) $group->total;
            $sum += $bytes * (int) $group->total;

            if ($max === null || $bytes > $max['bytes']) {
                $max = ['bytes' => $bytes, 'run_id' => (int) $group->last_id];
            }
        }

        if (!$count || $max === null) {
            return null;
        }

        $largest = CommandRun::query()->with('command')->find($max['run_id']);

        return [
            'average' => $this->memoryHelper->format($sum / $count),
            'max' => $this->memoryHelper->format($max['bytes']),
            'max_command' => $largest instanceof CommandRun
                ? $this->getName($largest->command?->class)
                : null,
        ];
    }

    private function getEmptyRuns(): array
    {
        return [
            'hours' => self::HOURS,
            'totals' => ['total' => 0] + array_fill_keys(self::STATES, 0),
            'hourly' => [],
            'duration' => null,
            'memory' => null,
            'running' => [],
            'errors' => [],
            'recent' => [],
        ];
    }

    /**
     * The start of the period: the current hour and the previous ones.
     */
    private function getStart(): Carbon
    {
        return now()->startOfHour()->subHours(self::HOURS - 1);
    }

    /**
     * @param Collection<int, CommandRun> $runs
     */
    private function mapRuns(Collection $runs): array
    {
        return $runs
            ->toBase()
            ->map(fn (CommandRun $run): array => $this->mapRun($run))
            ->values()
            ->all();
    }

    private function mapRun(CommandRun $run): array
    {
        return [
            'id' => (int) $run->id,
            'command' => $this->getName($run->command?->class),
            'class' => $run->command?->class,
            'state' => self::STATES[(int) $run->state] ?? 'unknown',
            'started_at' => $run->created_at?->toIso8601String(),
            'finished_at' => $run->state === 2
                ? null
                : $run->updated_at?->toIso8601String(),
            'duration' => $run->state === 2 || !$run->created_at || !$run->updated_at
                ? null
                : (int) $run->created_at->diffInSeconds($run->updated_at, true),
            'memory' => $run->memory,
            'pid' => $run->pid,
        ];
    }

    private function getFailedSchedules(): array
    {
        if (!$this->databaseHelper->hasColumn(Schedule::class, 'last_status')) {
            return [];
        }

        return $this
            ->scheduleRepository
            ->get()
            ->where('last_status', ScheduleStatusEnum::FAILED->value)
            ->map(fn (Schedule $schedule): array => [
                'id' => (int) $schedule->id,
                'name' => $schedule->name,
                'command' => $this->getName($schedule->command?->class),
                'class' => $schedule->command?->class,
                'last_run_at' => $schedule->last_run_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * The next runs of enabled schedules, the nearest first.
     */
    private function getUpcomingSchedules(): array
    {
        return $this
            ->scheduleRepository
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'schedule' => $schedule,
                'next_run_at' => $this->scheduleMonitor->getNextRunAt($schedule),
            ])
            ->filter(fn (array $item): bool => $item['next_run_at'] !== null)
            ->sortBy([
                fn (array $a, array $b): int => $a['next_run_at']->getTimestamp() <=> $b['next_run_at']->getTimestamp(),
                fn (array $a, array $b): int => $a['schedule']->id <=> $b['schedule']->id,
            ])
            ->take(self::LIMIT)
            ->map(fn (array $item): array => [
                'id' => (int) $item['schedule']->id,
                'name' => $item['schedule']->name,
                'command' => $this->getName($item['schedule']->command?->class),
                'next_run_at' => $item['next_run_at']->toIso8601String(),
                'lock' => $this->getLock($item['schedule']),
            ])
            ->values()
            ->all();
    }

    /**
     * The lock of withoutOverlapping when the runs of the schedule are skipped by it.
     */
    private function getLock(Schedule $schedule): ?array
    {
        $state = $this->scheduleMonitor->getLockState($schedule);

        return $state === null
            ? null
            : [
                'state' => $state,
                'since' => $schedule->last_run_at?->toIso8601String(),
                'skipped' => (int) $schedule->skipped_count,
            ];
    }

    private function getName(?string $class): string
    {
        if (!$class) {
            return 'Unknown';
        }

        $this->names ??= rescue(
            fn (): array => $this->artisanRepository->get()->map->getName()->all(),
            [],
            false,
        );

        return $this->names[$class] ?? class_basename($class);
    }
}
