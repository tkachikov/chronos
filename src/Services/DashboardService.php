<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Tkachikov\Chronos\Enums\ScheduleStatusEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Helpers\MemoryHelper;
use Tkachikov\Chronos\Models\Command;
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

    public const TOP = 8;

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
        return [
            'hours' => self::HOURS,
            'totals' => $this->getTotals(),
            'load' => $this->getLoad(),
            'top' => $this->getTopCommands(),
            'duration' => rescue(fn () => $this->getDuration(), null, false),
            'memory' => $this->getMemory(),
            'running' => $this->mapRuns($this->getLastRuns([2])),
            'errors' => $this->getErrors(),
            'recent' => $this->mapRuns($this->getLastRuns([0, 1, 3])),
        ];
    }

    /**
     * @param list<int> $states
     *
     * @return Collection<int, CommandRun>
     */
    private function getLastRuns(array $states): Collection
    {
        /** @var Collection<int, CommandRun> $runs */
        $runs = CommandRun::query()
            ->with('command')
            ->whereIn('state', $states)
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return $runs;
    }

    /**
     * Counts by states for the period in one query.
     *
     * @return array<string, int>
     */
    private function getTotals(): array
    {
        $selects = [];

        foreach (self::STATES as $state => $title) {
            $selects[] = "sum(case when state = $state then 1 else 0 end) as total_$title";
        }

        $row = (array) CommandRun::query()
            ->toBase()
            ->selectRaw(implode(', ', $selects))
            ->where('created_at', '>=', $this->getStart()->format('Y-m-d H:i:s'))
            ->first();

        $totals = ['total' => 0];

        foreach (self::STATES as $title) {
            $totals[$title] = (int) ($row["total_$title"] ?? 0);
            $totals['total'] += $totals[$title];
        }

        return $totals;
    }

    /**
     * Load of the commands by hours of the period: the peak of runs going on at once
     * and the peak of their memory in total (the memory of a run is known when it is finished).
     * Raw rows without models: there may be many runs in a day.
     */
    private function getLoad(): array
    {
        $now = now();
        $start = $this->getStart();
        $first = $start->getTimestamp();
        $classes = Command::query()->pluck('class', 'id');

        $rows = CommandRun::query()
            ->toBase()
            ->select(['command_id', 'state', 'memory', 'created_at', 'updated_at'])
            ->where(function (QueryBuilder $query) use ($start): void {
                $query
                    ->where('state', 2)
                    ->orWhere('updated_at', '>=', $start->format('Y-m-d H:i:s'));
            })
            ->get();

        /** @var array<int, list<array{from: int, to: int, memory: float, command: string}>> $buckets */
        $buckets = array_fill(0, self::HOURS, []);

        foreach ($rows as $row) {
            $startedAt = $row->created_at ? strtotime((string) $row->created_at) : false;

            if ($startedAt === false) {
                continue;
            }

            // A running run goes on up to now
            $finishedAt = (int) $row->state === 2 || !$row->updated_at
                ? $now->getTimestamp() + 1
                : (int) strtotime((string) $row->updated_at);

            $run = [
                'from' => $startedAt,
                // A run shorter than a second is still a run of its hour
                'to' => max($finishedAt, $startedAt + 1),
                'memory' => $this->memoryHelper->toBytes($row->memory === null ? null : (string) $row->memory) ?? 0.0,
                'command' => $this->getName($classes->get((int) $row->command_id)),
            ];

            // Finished before the period, e.g. at its start
            if ($run['to'] <= $first) {
                continue;
            }

            $firstHour = max(0, (int) floor(($run['from'] - $first) / 3600));
            $lastHour = min(self::HOURS - 1, (int) floor(($run['to'] - 1 - $first) / 3600));

            for ($hour = $firstHour; $hour <= $lastHour; $hour++) {
                $buckets[$hour][] = $run;
            }
        }

        $points = [];
        $maxRunning = 0;
        $maxMemory = 0.0;

        foreach ($buckets as $hour => $active) {
            $from = $first + $hour * 3600;
            [$running, $memory] = $this->getPeaks($active, $from, $from + 3600);

            $maxRunning = max($maxRunning, $running);
            $maxMemory = max($maxMemory, $memory);

            // The commands with the most runs in the hour
            $commands = array_count_values(array_column($active, 'command'));
            arsort($commands);

            $points[] = [
                'hour' => $start->copy()->addHours($hour)->toIso8601String(),
                'runs' => count($active),
                'running' => $running,
                'memory' => $memory > 0 ? $this->memoryHelper->format($memory) : null,
                'memory_bytes' => (int) $memory,
                'commands' => array_slice(array_keys($commands), 0, 5),
                'more_commands' => max(0, count($commands) - 5),
            ];
        }

        return [
            'hours' => self::HOURS,
            'points' => $points,
            'max_running' => $maxRunning,
            'max_memory' => $maxMemory > 0 ? $this->memoryHelper->format($maxMemory) : null,
        ];
    }

    /**
     * Commands of the period with the most time of runs: they load the server the most.
     * By the number of runs for a database whose time functions are unknown.
     */
    private function getTopCommands(): array
    {
        $start = $this->getStart()->format('Y-m-d H:i:s');
        $diff = rescue(fn () => $this->databaseHelper->getTimeDiffInSeconds('created_at', 'updated_at'), null, false);
        $selects = [
            'command_id',
            'count(*) as runs',
            'sum(case when state in (1, 3) then 1 else 0 end) as errors',
        ];

        if ($diff) {
            // Without running and killed runs: their time is not known
            $selects[] = "sum(case when state in (0, 1) then $diff else 0 end) as total_time";
            $selects[] = "avg(case when state in (0, 1) then $diff end) as average_time";
            $selects[] = "max(case when state in (0, 1) then $diff end) as max_time";
        }

        $groups = CommandRun::query()
            ->toBase()
            ->selectRaw(implode(', ', $selects))
            ->where('created_at', '>=', $start)
            ->groupBy('command_id')
            ->orderByRaw($diff ? 'total_time desc' : 'runs desc')
            ->orderByDesc('runs')
            ->limit(self::TOP)
            ->get();

        if ($groups->isEmpty()) {
            return [];
        }

        $classes = Command::query()->pluck('class', 'id');
        $memory = [];

        // Memory is stored as a string with units: the largest by commands and their few values
        CommandRun::query()
            ->toBase()
            ->select(['command_id', 'memory'])
            ->whereIn('command_id', $groups->pluck('command_id'))
            ->whereNotNull('memory')
            ->where('created_at', '>=', $start)
            ->groupBy('command_id', 'memory')
            ->get()
            ->each(function (object $row) use (&$memory): void {
                $bytes = $this->memoryHelper->toBytes((string) $row->memory);
                $id = (int) $row->command_id;

                if ($bytes !== null && $bytes > ($memory[$id] ?? 0)) {
                    $memory[$id] = $bytes;
                }
            });

        return $groups
            ->map(fn (object $group): array => [
                'command' => $this->getName($classes->get((int) $group->command_id)),
                'runs' => (int) $group->runs,
                'errors' => (int) $group->errors,
                'total_time' => isset($group->total_time) ? (int) round((float) $group->total_time) : null,
                'average_time' => isset($group->average_time) ? round((float) $group->average_time, 1) : null,
                'max_time' => isset($group->max_time) ? (int) $group->max_time : null,
                'max_memory' => isset($memory[(int) $group->command_id])
                    ? $this->memoryHelper->format($memory[(int) $group->command_id])
                    : null,
            ])
            ->values()
            ->all();
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

    /**
     * Peaks of runs at once and of their memory within the interval: by starts and finishes in order of time.
     *
     * @param array<array{from: int, to: int, memory: float, command?: string}> $runs
     *
     * @return array{0: int, 1: float}
     */
    private function getPeaks(array $runs, int $from, int $to): array
    {
        $events = [];

        foreach ($runs as $run) {
            $events[] = [max($run['from'], $from), 1, $run['memory']];
            $events[] = [min($run['to'], $to), -1, -$run['memory']];
        }

        // A finish before a start at the same second: the runs did not go on at once
        usort($events, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $running = $peakRunning = 0;
        $memory = $peakMemory = 0.0;

        foreach ($events as [, $delta, $bytes]) {
            $running += $delta;
            $memory += $bytes;
            $peakRunning = max($peakRunning, $running);
            $peakMemory = max($peakMemory, $memory);
        }

        return [$peakRunning, $peakMemory];
    }

    private function getEmptyRuns(): array
    {
        return [
            'hours' => self::HOURS,
            'totals' => ['total' => 0] + array_fill_keys(self::STATES, 0),
            'load' => null,
            'top' => [],
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
