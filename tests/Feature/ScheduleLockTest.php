<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\CommandRunReaperService;
use Tkachikov\Chronos\Services\ScheduleMonitorService;
use Tkachikov\Chronos\Services\SchedulerHeartbeatService;

final class ScheduleLockTest extends TestCase
{
    public function testHeldWhileRunning(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $this->app->make(SchedulerHeartbeatService::class)->beat();
        $schedule = $this->createSkippedSchedule($model);
        $this->createRun($model, 2, ['pid' => getmypid(), 'host' => gethostname()]);

        $this->assertSame(ScheduleMonitorService::LOCK_HELD, $this->lockState($schedule));

        $this
            ->getJson('/chronos/health')
            ->assertOk()
            ->assertJsonPath('schedules.stuck', [])
            ->assertJsonPath('schedules.missed', []);

        $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('schedules.upcoming.0.lock', [
                'state' => 'held',
                'since' => '2026-10-16T09:50:00+00:00',
                'skipped' => 10,
            ]);

        $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            ->assertSee('Skipped 10 times')
            ->assertDontSee('Lock is stuck');
    }

    public function testStuckWithoutRunningRun(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $this->app->make(SchedulerHeartbeatService::class)->beat();
        $schedule = $this->createSkippedSchedule($model, ['name' => 'Import #1']);
        $this->createRun($model, 0);

        $this->assertSame(ScheduleMonitorService::LOCK_STUCK, $this->lockState($schedule));

        $this
            ->getJson('/chronos/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'fail')
            ->assertJsonPath('scheduler.status', 'ok')
            ->assertJsonPath('schedules.missed', [])
            ->assertJsonPath('schedules.stuck', [[
                'id' => $schedule->id,
                'name' => 'Import #1',
                'command' => 'App\\Console\\Commands\\LockTest',
                'last_run_at' => '2026-10-16T09:50:00+00:00',
                'skipped' => 10,
                'last_skipped_at' => '2026-10-16T10:00:00+00:00',
            ]]);

        $this
            ->artisan('chronos:health')
            ->expectsOutputToContain('1 running, 1 stuck')
            ->expectsOutputToContain('Import #1: stuck lock, skipped 10 times since 2026-10-16T09:50:00')
            ->assertFailed();

        $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('schedules.stuck.0.command', 'app:lock-test')
            ->assertJsonPath('schedules.upcoming.0.lock.state', 'stuck');

        $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            ->assertSee('Lock is stuck, skipped 10 times');
    }

    public function testNoLockAfterRunFinished(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $this->app->make(SchedulerHeartbeatService::class)->beat();
        $schedule = $this->createSkippedSchedule($model);

        // The run finished after the skips (in the same second too), the next run has not started yet
        DB::table('schedules')->where('id', $schedule->id)->update([
            'last_status' => 'success',
            'last_finished_at' => '2026-10-16 10:00:00',
        ]);
        $this->assertNull($this->lockState($schedule));

        $this
            ->getJson('/chronos/health')
            ->assertOk()
            ->assertJsonPath('schedules.stuck', []);

        // A skip after the finish: the lock is held by nothing
        DB::table('schedules')->where('id', $schedule->id)->update([
            'last_skipped_at' => '2026-10-16 10:01:00',
        ]);
        $this->assertSame(ScheduleMonitorService::LOCK_STUCK, $this->lockState($schedule));
    }

    public function testNoLockWithoutSkipsOrOverlapping(): void
    {
        $model = $this->model();

        $this->assertNull($this->lockState($this->createSkippedSchedule($model, ['skipped_count' => 0])));
        $this->assertNull($this->lockState($this->createSkippedSchedule($model, ['without_overlapping' => false])));
        $this->assertNull($this->lockState($this->createSkippedSchedule($model, ['run' => false])));
    }

    public function testHeldForCommandsWithoutTrait(): void
    {
        $this->makeCommand('LockPlain', 'app:lock-plain');
        $this->app->make(ArtisanRepositoryInterface::class)->load();
        $model = $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\LockPlain')
            ->getModel();

        // The runs of the command are not saved, so it is unknown whether it is running
        $this->assertSame(ScheduleMonitorService::LOCK_HELD, $this->lockState($this->createSkippedSchedule($model)));
    }

    public function testSkipsAreNotMissed(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $schedule = $this->createSkippedSchedule($model);
        $monitor = $this->app->make(ScheduleMonitorService::class);

        $this->travelTo('2026-10-16 10:02:00');
        $this->assertNull($monitor->getMissedSince($schedule->refresh()));

        // The skips stopped
        $this->travelTo('2026-10-16 10:05:00');
        $this->assertSame('2026-10-16 10:01:00', $monitor->getMissedSince($schedule->refresh())?->toDateTimeString());
    }

    public function testRunSavesPidAndHost(): void
    {
        $this->model();

        $this->artisan('app:lock-test')->assertSuccessful();

        $run = CommandRun::query()->latest('id')->firstOrFail();
        $this->assertSame(getmypid(), (int) $run->pid);
        $this->assertSame(gethostname(), $run->host);
    }

    public function testReaperFinishesRunsWithGoneProcesses(): void
    {
        $model = $this->model();
        $deadPid = (int) exec('sh -c \'echo $$\'');

        $gone = $this->createRun($model, 2, ['pid' => $deadPid, 'host' => gethostname()]);
        $alive = $this->createRun($model, 2, ['pid' => getmypid(), 'host' => gethostname()]);
        $otherHost = $this->createRun($model, 2, ['pid' => $deadPid, 'host' => 'other-host']);
        $finished = $this->createRun($model, 0, ['pid' => $deadPid, 'host' => gethostname()]);

        $this->assertSame(1, $this->app->make(CommandRunReaperService::class)->reap());

        $this->assertSame(3, (int) $gone->refresh()->state);
        $this->assertSame(2, (int) $alive->refresh()->state);
        $this->assertSame(2, (int) $otherHost->refresh()->state);
        $this->assertSame(0, (int) $finished->refresh()->state);
        $this->assertSame(
            "The process $deadPid is gone without finishing the run",
            CommandLog::query()->where('command_run_id', $gone->id)->value('message'),
        );
    }

    public function testReaperRunsOnScheduleRun(): void
    {
        $model = $this->model();
        $schedule = $this->createSkippedSchedule($model);
        $deadPid = (int) exec('sh -c \'echo $$\'');
        $gone = $this->createRun($model, 2, ['pid' => $deadPid, 'host' => gethostname()]);

        $this->assertSame(ScheduleMonitorService::LOCK_HELD, $this->lockState($schedule));

        event(new CommandStarting('schedule:run', new ArrayInput([]), new NullOutput()));

        $this->assertSame(3, (int) $gone->refresh()->state);
        $this->assertSame(ScheduleMonitorService::LOCK_STUCK, $this->lockState($schedule));
    }

    public function testAliveProcess(): void
    {
        $reaper = $this->app->make(CommandRunReaperService::class);

        $this->assertTrue($reaper->isAlive(getmypid()));
        $this->assertFalse($reaper->isAlive((int) exec('sh -c \'echo $$\'')));
        $this->assertFalse($reaper->isAlive(0));
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
    }

    private function lockState(Schedule $schedule): ?string
    {
        return $this
            ->app
            ->make(ScheduleMonitorService::class)
            ->getLockState($schedule->refresh());
    }

    /**
     * A schedule whose runs are skipped by withoutOverlapping since 09:50.
     */
    private function createSkippedSchedule(Command $model, array $attributes = []): Schedule
    {
        $schedule = Schedule::create([
            'command_id' => $model->id,
            'args' => [],
            'time_method' => 'everyMinute',
            'without_overlapping' => true,
            'run' => true,
            ...$attributes,
        ]);

        DB::table('schedules')->where('id', $schedule->id)->update([
            'created_at' => '2026-10-16 09:00:00',
            'updated_at' => '2026-10-16 09:00:00',
            'last_run_at' => '2026-10-16 09:50:00',
            'last_status' => 'running',
            'last_skipped_at' => '2026-10-16 10:00:00',
            'skipped_count' => $attributes['skipped_count'] ?? 10,
        ]);

        return $schedule;
    }

    private function createRun(Command $model, int $state, array $attributes = []): CommandRun
    {
        return CommandRun::create([
            'command_id' => $model->id,
            'state' => $state,
            ...$attributes,
        ]);
    }

    private function model(): Command
    {
        // A unique class: makeCommand() requires a class once, other tests have Test without the trait
        $this->makeCommand('LockTest', 'app:lock-test', true);

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        return $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\LockTest')
            ->getModel();
    }
}
