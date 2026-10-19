<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Exception;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;

final class ScheduleTrackerTest extends TestCase
{
    public function testSuccess(): void
    {
        [$schedule, $event] = $this->createSchedule();

        $this->travelTo('2026-10-16 10:00:00');
        event(new ScheduledTaskStarting($event));

        $schedule->refresh();
        $this->assertSame('running', $schedule->last_status);
        $this->assertSame('2026-10-16 10:00:00', $schedule->last_run_at?->toDateTimeString());
        $this->assertNull($schedule->last_finished_at);

        $this->travelTo('2026-10-16 10:00:30');
        $event->exitCode = 0;
        event(new ScheduledTaskFinished($event, 30));

        $schedule->refresh();
        $this->assertSame('success', $schedule->last_status);
        $this->assertSame('2026-10-16 10:00:00', $schedule->last_run_at?->toDateTimeString());
        $this->assertSame('2026-10-16 10:00:30', $schedule->last_finished_at?->toDateTimeString());
    }

    public function testFailedByExitCode(): void
    {
        [$schedule, $event] = $this->createSchedule();

        event(new ScheduledTaskStarting($event));
        $event->exitCode = 1;
        event(new ScheduledTaskFinished($event, 1));
        event(new ScheduledTaskFailed($event, new Exception('Failed')));

        $this->assertSame('failed', $schedule->refresh()->last_status);
        $this->assertNotNull($schedule->last_finished_at);
    }

    public function testSkippedByOverlapping(): void
    {
        [$schedule, $event] = $this->createSchedule();

        event(new ScheduledTaskStarting($event));
        event(new ScheduledTaskFinished($event, 0));

        $this->assertSame('skipped', $schedule->refresh()->last_status);
        $this->assertNotNull($schedule->last_run_at);
    }

    public function testSkipsKeepRunningRun(): void
    {
        [$schedule, $event] = $this->createSchedule(['without_overlapping' => true]);

        $this->travelTo('2026-10-16 10:00:00');
        event(new ScheduledTaskStarting($event));

        $this->travelTo('2026-10-16 10:01:00');
        event(new ScheduledTaskSkipped($event));
        $this->travelTo('2026-10-16 10:02:00');
        event(new ScheduledTaskSkipped($event));

        $schedule->refresh();
        $this->assertSame('running', $schedule->last_status);
        $this->assertSame('2026-10-16 10:00:00', $schedule->last_run_at?->toDateTimeString());
        $this->assertSame('2026-10-16 10:02:00', $schedule->last_skipped_at?->toDateTimeString());
        $this->assertSame(2, $schedule->skipped_count);

        $this->travelTo('2026-10-16 10:02:30');
        $event->exitCode = 0;
        event(new ScheduledTaskFinished($event, 150));

        $schedule->refresh();
        $this->assertSame('success', $schedule->last_status);
        $this->assertSame('2026-10-16 10:00:00', $schedule->last_run_at?->toDateTimeString());
        $this->assertSame(2, $schedule->skipped_count);

        // The next run starts the count again
        $this->travelTo('2026-10-16 10:03:00');
        event(new ScheduledTaskStarting($event));

        $schedule->refresh();
        $this->assertSame(0, $schedule->skipped_count);
        $this->assertSame('2026-10-16 10:02:00', $schedule->last_skipped_at?->toDateTimeString());
    }

    public function testSkippedByFilters(): void
    {
        [$schedule, $event] = $this->createSchedule();

        event(new ScheduledTaskSkipped($event));

        $schedule->refresh();
        $this->assertNull($schedule->last_status);
        $this->assertNull($schedule->last_run_at);
        $this->assertNotNull($schedule->last_skipped_at);
        $this->assertSame(1, $schedule->skipped_count);
    }

    public function testBackground(): void
    {
        [$schedule, $event] = $this->createSchedule(['run_in_background' => true]);

        $this->assertTrue($event->runInBackground);

        event(new ScheduledTaskStarting($event));
        event(new ScheduledTaskFinished($event, 0));

        $this->assertSame('running', $schedule->refresh()->last_status);
        $this->assertNull($schedule->last_finished_at);

        // schedule:finish runs in another process, so the events are created again
        $event = $this->getEvent();
        $event->exitCode = 1;
        event(new ScheduledBackgroundTaskFinished($event));

        $this->assertSame('failed', $schedule->refresh()->last_status);
        $this->assertNotNull($schedule->last_finished_at);
    }

    public function testUpdatedAtIsNotChanged(): void
    {
        [$schedule, $event] = $this->createSchedule();
        $updatedAt = $schedule->updated_at?->toDateTimeString();

        $this->travelTo(now()->addDay());
        event(new ScheduledTaskStarting($event));

        $this->assertSame($updatedAt, $schedule->refresh()->updated_at?->toDateTimeString());
    }

    public function testEventsOfOtherTasksAreIgnored(): void
    {
        [$schedule] = $this->createSchedule();

        $scheduler = $this->app->make(ScheduleConsole::class);
        $other = $scheduler->command('app:other')->everyMinute();

        event(new ScheduledTaskStarting($other));

        $this->assertNull($schedule->refresh()->last_status);
    }

    /**
     * @return array{Schedule, Event}
     */
    private function createSchedule(array $attributes = []): array
    {
        $this->makeCommand(withChronosTrait: true);

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        $model = $this
            ->app
            ->make(CommandManager::class)
            ->getApps()
            ->first()
            ->getModel();

        $schedule = Schedule::create([
            'command_id' => $model->id,
            'args' => [],
            'time_method' => 'everyMinute',
            'run' => true,
            ...$attributes,
        ]);

        return [$schedule, $this->getEvent()];
    }

    private function getEvent(): Event
    {
        $this
            ->app
            ->forgetInstance(ScheduleConsole::class);

        $events = $this
            ->app
            ->make(ScheduleConsole::class)
            ->events();

        $this->assertCount(1, $events);

        return $events[0];
    }
}
