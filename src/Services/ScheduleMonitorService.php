<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

/**
 * Next and missed runs of schedules by their cron expressions.
 */
class ScheduleMonitorService
{
    /**
     * A run is missed when it has not started within this number of minutes after it was due.
     */
    public const MISSED_AFTER_MINUTES = 2;

    /**
     * The lock of withoutOverlapping is held by a running run: the next runs are skipped as expected.
     */
    public const LOCK_HELD = 'held';

    /**
     * The runs are skipped by the lock of withoutOverlapping, but no run of the command is running:
     * the process that held it is gone (e.g. a restart of the server), the lock is held until it expires.
     */
    public const LOCK_STUCK = 'stuck';

    private ?bool $hasColumns = null;

    private ?bool $hasSkipColumns = null;

    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    public function getNextRunAt(Schedule $schedule): ?Carbon
    {
        if (!$schedule->run) {
            return null;
        }

        return $this->getEvent($schedule)?->nextRunDate(now());
    }

    /**
     * The first due time since which the schedule has not started.
     */
    public function getMissedSince(Schedule $schedule): ?Carbon
    {
        if (
            !$schedule->run
            || !$this->hasColumns()
        ) {
            return null;
        }

        $event = $this->getEvent($schedule);

        if (!$event) {
            return null;
        }

        $since = max(
            $schedule->created_at?->getTimestamp() ?? 0,
            $schedule->updated_at?->getTimestamp() ?? 0,
            $schedule->last_run_at?->getTimestamp() ?? 0,
            // A skip is not a missed run: the scheduler has seen the schedule
            $this->hasSkipColumns()
                ? ($schedule->last_skipped_at?->getTimestamp() ?? 0)
                : 0,
        );

        $dueAt = $event->nextRunDate(Date::createFromTimestamp($since, now()->getTimezone()));

        return $dueAt->lessThanOrEqualTo(now()->subMinutes(self::MISSED_AFTER_MINUTES))
            ? $dueAt
            : null;
    }

    /**
     * The state of the lock of withoutOverlapping by the last skips of the schedule, null when the runs are not skipped.
     * The state of the run is known only for commands with ChronosRunnerTrait, for the others the lock is considered held.
     *
     * @return self::LOCK_*|null
     */
    public function getLockState(Schedule $schedule): ?string
    {
        if (
            !$schedule->run
            || !$schedule->without_overlapping
            || !$this->hasSkipColumns()
            || !$schedule->skipped_count
        ) {
            return null;
        }

        $class = $schedule->command?->class;

        if (
            !$class
            || !class_exists($class)
            || !in_array(ChronosRunnerTrait::class, class_uses_recursive($class), true)
        ) {
            return self::LOCK_HELD;
        }

        $running = CommandRun::query()
            ->where('command_id', $schedule->command_id)
            ->where('state', 2)
            ->exists();

        return $running
            ? self::LOCK_HELD
            : self::LOCK_STUCK;
    }

    private function getEvent(Schedule $schedule): ?Event
    {
        return rescue(
            fn () => $this->scheduleService->register(new ScheduleConsole(), $schedule),
            report: false,
        );
    }

    private function hasColumns(): bool
    {
        return $this->hasColumns ??= $this->databaseHelper->hasColumn(Schedule::class, 'last_status');
    }

    private function hasSkipColumns(): bool
    {
        return $this->hasSkipColumns ??= $this->databaseHelper->hasColumn(Schedule::class, 'skipped_count');
    }
}
