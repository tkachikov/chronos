<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Tkachikov\Chronos\Enums\ScheduleStatusEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Schedule;
use WeakMap;

/**
 * Saves the last run of schedules by the events of the Laravel scheduler.
 */
class ScheduleTrackerService
{
    /** @var WeakMap<Event, int> */
    private WeakMap $schedules;

    private ?bool $hasColumns = null;

    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
        $this->schedules = new WeakMap();
    }

    public function track(Event $event, Schedule $schedule): void
    {
        $this->schedules[$event] = $schedule->id;
    }

    public function handle(
        ScheduledTaskStarting|ScheduledTaskFinished|ScheduledTaskFailed|ScheduledTaskSkipped|ScheduledBackgroundTaskFinished $event,
    ): void {
        $scheduleId = $this->schedules[$event->task] ?? null;

        if ($scheduleId === null) {
            return;
        }

        $values = match (true) {
            $event instanceof ScheduledTaskStarting => [
                'last_run_at' => now(),
                'last_finished_at' => null,
                'last_status' => ScheduleStatusEnum::RUNNING,
            ],
            $event instanceof ScheduledTaskSkipped => [
                'last_run_at' => now(),
                'last_finished_at' => null,
                'last_status' => ScheduleStatusEnum::SKIPPED,
            ],
            $event instanceof ScheduledTaskFailed => $this->finished(ScheduleStatusEnum::FAILED),
            $event instanceof ScheduledTaskFinished => $this->getFinishedValues($event->task),
            $event instanceof ScheduledBackgroundTaskFinished => $this->finished(
                $event->task->exitCode === 0 ? ScheduleStatusEnum::SUCCESS : ScheduleStatusEnum::FAILED,
            ),
        };

        if (
            $values === []
            || !$this->hasColumns()
        ) {
            return;
        }

        $values['last_status'] = $values['last_status']->value;

        // Without touching updated_at: it is the time of the last change of the schedule
        Schedule::query()
            ->whereKey($scheduleId)
            ->toBase()
            ->update($values);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFinishedValues(Event $task): array
    {
        return match (true) {
            // Finishes in schedule:finish
            $task->runInBackground && $task->exitCode === null => [],
            // Skipped because of withoutOverlapping
            $task->exitCode === null => ['last_status' => ScheduleStatusEnum::SKIPPED],
            default => $this->finished(
                $task->exitCode === 0 ? ScheduleStatusEnum::SUCCESS : ScheduleStatusEnum::FAILED,
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function finished(ScheduleStatusEnum $status): array
    {
        return [
            'last_finished_at' => now(),
            'last_status' => $status,
        ];
    }

    private function hasColumns(): bool
    {
        return $this->hasColumns ??= $this->databaseHelper->hasColumn(Schedule::class, 'last_status');
    }
}
