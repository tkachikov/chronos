<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Schedule;

/**
 * Next and missed runs of schedules by their cron expressions.
 */
class ScheduleMonitorService
{
    /**
     * A run is missed when it has not started within this number of minutes after it was due.
     */
    public const MISSED_AFTER_MINUTES = 2;

    private ?bool $hasColumns = null;

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
        );

        $dueAt = $event->nextRunDate(Date::createFromTimestamp($since, now()->getTimezone()));

        return $dueAt->lessThanOrEqualTo(now()->subMinutes(self::MISSED_AFTER_MINUTES))
            ? $dueAt
            : null;
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
}
