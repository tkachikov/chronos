<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tkachikov\Chronos\Models\SchedulerHeartbeat;
use Tkachikov\Chronos\Services\SchedulerHeartbeatService;

final class SchedulerHeartbeatTest extends TestCase
{
    public function testScheduleRunSavesHeartbeat(): void
    {
        $this->travelTo('2026-10-16 10:00:00');

        $this->startCommand('schedule:run');

        $heartbeat = SchedulerHeartbeat::sole();

        $this->assertSame(gethostname(), $heartbeat->host);
        $this->assertSame('2026-10-16 10:00:00', $heartbeat->last_run_at->toDateTimeString());

        $this->travelTo('2026-10-16 10:01:00');

        $this->startCommand('schedule:run');

        $this->assertSame('2026-10-16 10:01:00', SchedulerHeartbeat::sole()->last_run_at->toDateTimeString());
    }

    public function testOtherCommandsDoNotSaveHeartbeat(): void
    {
        $this->startCommand('schedule:list');

        $this->assertSame(0, SchedulerHeartbeat::count());
    }

    public function testIsAlive(): void
    {
        $service = $this->app->make(SchedulerHeartbeatService::class);

        $this->assertFalse($service->isAlive());

        $this->travelTo('2026-10-16 10:00:00');
        $service->beat();

        $this->travelTo('2026-10-16 10:05:00');
        $this->assertTrue($service->isAlive());

        $this->travelTo('2026-10-16 10:05:01');
        $this->assertFalse($service->isAlive());

        config(['chronos.monitoring.scheduler_timeout' => 10]);
        $this->assertTrue($service->isAlive());
    }

    public function testLastRunAtIsLatestOfAllHosts(): void
    {
        SchedulerHeartbeat::create(['host' => 'web-1', 'last_run_at' => '2026-10-16 09:00:00']);
        SchedulerHeartbeat::create(['host' => 'web-2', 'last_run_at' => '2026-10-16 10:00:00']);

        $service = $this->app->make(SchedulerHeartbeatService::class);

        $this->assertSame('2026-10-16 10:00:00', $service->getLastRunAt()?->toDateTimeString());
        $this->assertSame(['web-2', 'web-1'], $service->get()->pluck('host')->all());
    }

    /**
     * Laravel 11+ dispatches CommandStarting only outside of unit tests.
     */
    private function startCommand(string $command): void
    {
        event(new CommandStarting($command, new ArrayInput([]), new NullOutput()));
    }
}
