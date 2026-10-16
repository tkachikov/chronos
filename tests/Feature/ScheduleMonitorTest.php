<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\ScheduleMonitorService;

final class ScheduleMonitorTest extends TestCase
{
    public function testNextRunAt(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $schedule = $this->createSchedule('hourlyAt', [15]);

        $this->assertSame('2026-10-16 10:15:00', $this->service()->getNextRunAt($schedule)?->toDateTimeString());
    }

    public function testNextRunAtOfDisabledSchedule(): void
    {
        $schedule = $this->createSchedule(attributes: ['run' => false]);

        $this->assertNull($this->service()->getNextRunAt($schedule));
    }

    public function testNotMissedRightAfterCreation(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $schedule = $this->createSchedule();

        $this->travelTo('2026-10-16 10:02:59');
        $this->assertNull($this->service()->getMissedSince($schedule));
    }

    public function testMissed(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $schedule = $this->createSchedule();

        $this->travelTo('2026-10-16 10:03:00');
        $this->assertSame('2026-10-16 10:01:00', $this->service()->getMissedSince($schedule)?->toDateTimeString());

        $this->travelTo('2026-10-16 10:10:00');
        $this->assertSame('2026-10-16 10:01:00', $this->service()->getMissedSince($schedule)?->toDateTimeString());
    }

    public function testNotMissedWhenStarted(): void
    {
        $this->travelTo('2026-10-16 09:00:00');
        $schedule = $this->createSchedule('hourly');

        $this->travelTo('2026-10-16 10:30:00');
        $this->assertSame('2026-10-16 10:00:00', $this->service()->getMissedSince($schedule)?->toDateTimeString());

        $this->setLastRunAt($schedule, '2026-10-16 10:00:01');
        $this->assertNull($this->service()->getMissedSince($schedule->refresh()));

        $this->travelTo('2026-10-16 11:01:59');
        $this->assertNull($this->service()->getMissedSince($schedule));

        $this->travelTo('2026-10-16 11:02:00');
        $this->assertSame('2026-10-16 11:00:00', $this->service()->getMissedSince($schedule)?->toDateTimeString());
    }

    public function testNotMissedWhenDisabled(): void
    {
        $this->travelTo('2026-10-16 09:00:00');
        $schedule = $this->createSchedule(attributes: ['run' => false]);

        $this->travelTo('2026-10-16 10:00:00');
        $this->assertNull($this->service()->getMissedSince($schedule));
    }

    public function testNotMissedWhenChangedAfterDueTime(): void
    {
        $this->travelTo('2026-10-16 09:00:00');
        $schedule = $this->createSchedule('hourly');

        $this->travelTo('2026-10-16 10:30:00');
        $schedule->update(['time_method' => 'daily']);

        $this->travelTo('2026-10-16 10:40:00');
        $this->assertNull($this->service()->getMissedSince($schedule));
    }

    public function testNextRunAtWithParams(): void
    {
        $this->travelTo('2026-10-16 09:00:00');
        $schedule = $this->createSchedule('dailyAt', ['01:00']);

        $this->assertSame('2026-10-17 01:00:00', $this->service()->getNextRunAt($schedule)?->toDateTimeString());
    }

    private function service(): ScheduleMonitorService
    {
        return $this->app->make(ScheduleMonitorService::class);
    }

    private function setLastRunAt(Schedule $schedule, string $time): void
    {
        DB::table('schedules')
            ->where('id', $schedule->id)
            ->update(['last_run_at' => $time]);
    }

    private function createSchedule(
        string $method = 'everyMinute',
        ?array $params = null,
        array $attributes = [],
    ): Schedule {
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

        return Schedule::create([
            'command_id' => $model->id,
            'args' => [],
            'time_method' => $method,
            'time_params' => $params,
            'run' => true,
            ...$attributes,
        ]);
    }
}
