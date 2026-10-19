<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\SchedulerHeartbeatService;

final class DashboardTest extends TestCase
{
    public function testPage(): void
    {
        $this
            ->get('/chronos/dashboard')
            ->assertOk()
            ->assertSee('Running now')
            ->assertSee(json_encode(route('chronos.dashboard.data')), false);
    }

    public function testEmpty(): void
    {
        $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('scheduler.status', 'ok')
            ->assertJsonPath('runs.totals', ['total' => 0, 'success' => 0, 'failed' => 0, 'running' => 0, 'killed' => 0])
            ->assertJsonCount(24, 'runs.hourly')
            ->assertJsonPath('runs.duration', null)
            ->assertJsonPath('runs.memory', null)
            ->assertJsonPath('runs.running', [])
            ->assertJsonPath('runs.errors', [])
            ->assertJsonPath('runs.recent', [])
            ->assertJsonPath('schedules.upcoming', []);
    }

    public function testRuns(): void
    {
        $this->travelTo('2026-10-16 12:30:00');
        $model = $this->model();

        $this->createRun($model, 0, '2026-10-16 12:10:00', '2026-10-16 12:10:05', ['memory' => '10 MB']);
        $this->createRun($model, 1, '2026-10-16 11:20:00', '2026-10-16 11:20:01', ['memory' => '30 MB']);
        // Without the time: the time of killed runs is not reliable
        $this->createRun($model, 3, '2026-10-16 12:15:00', '2026-10-16 12:16:00', ['memory' => '20 MB']);
        $running = $this->createRun($model, 2, '2026-10-16 12:29:00', '2026-10-16 12:29:00', ['pid' => 123]);
        // Out of the period.
        $this->createRun($model, 1, '2026-10-15 12:59:59', '2026-10-15 13:00:00');

        $response = $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('runs.totals', ['total' => 4, 'success' => 1, 'failed' => 1, 'running' => 1, 'killed' => 1])
            ->assertJsonPath('runs.duration', ['average' => 3, 'max' => 5, 'max_command' => 'app:test'])
            ->assertJsonPath('runs.memory', ['average' => '20 MB', 'max' => '30 MB', 'max_command' => 'app:test'])
            ->assertJsonPath('runs.hourly.0.hour', '2026-10-15T13:00:00+00:00')
            ->assertJsonPath('runs.hourly.22', ['hour' => '2026-10-16T11:00:00+00:00', 'success' => 0, 'failed' => 1])
            ->assertJsonPath('runs.hourly.23', ['hour' => '2026-10-16T12:00:00+00:00', 'success' => 1, 'failed' => 1])
            ->assertJsonCount(1, 'runs.running')
            ->assertJsonPath('runs.running.0.id', $running->id)
            ->assertJsonPath('runs.running.0.command', 'app:test')
            ->assertJsonPath('runs.running.0.state', 'running')
            ->assertJsonPath('runs.running.0.pid', 123)
            ->assertJsonPath('runs.running.0.duration', null)
            ->assertJsonCount(1, 'runs.errors')
            ->assertJsonPath('runs.errors.0.command', 'app:test')
            ->assertJsonPath('runs.errors.0.class', 'App\\Console\\Commands\\Test')
            ->assertJsonPath('runs.errors.0.total', 2)
            ->assertJsonPath('runs.errors.0.failed', 1)
            ->assertJsonPath('runs.errors.0.killed', 1)
            ->assertJsonPath('runs.errors.0.last.state', 'killed')
            ->assertJsonPath('runs.errors.0.last.started_at', '2026-10-16T12:15:00+00:00')
            ->assertJsonPath('runs.errors.0.last.duration', 60)
            ->assertJsonCount(4, 'runs.recent')
            ->assertJsonPath('runs.recent.0.state', 'failed')
            ->assertJsonPath('runs.recent.0.started_at', '2026-10-15T12:59:59+00:00');

        $this->assertSame(
            ['failed', 'killed', 'failed', 'success'],
            array_column($response->json('runs.recent'), 'state'),
        );
    }

    public function testStoppedSchedulerMissedAndFailedSchedules(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $schedule = $this->createSchedule($model, ['name' => 'Import #1']);
        $this->app->make(SchedulerHeartbeatService::class)->beat();

        DB::table('schedules')->where('id', $schedule->id)->update([
            'last_run_at' => '2026-10-16 10:00:30',
            'last_status' => 'failed',
        ]);

        $this->travelTo('2026-10-16 10:10:00');

        $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonPath('scheduler.status', 'stopped')
            ->assertJsonPath('schedules.enabled', 1)
            ->assertJsonPath('schedules.missed.0.name', 'Import #1')
            ->assertJsonPath('schedules.missed.0.command', 'app:test')
            ->assertJsonPath('schedules.missed.0.missed_since', '2026-10-16T10:01:00+00:00')
            ->assertJsonPath('schedules.failed', [[
                'id' => $schedule->id,
                'name' => 'Import #1',
                'command' => 'app:test',
                'class' => 'App\\Console\\Commands\\Test',
                'last_run_at' => '2026-10-16T10:00:30+00:00',
            ]]);
    }

    public function testErrorsAreGroupedByCommands(): void
    {
        $this->travelTo('2026-10-16 12:30:00');
        $test = $this->model();
        $this->makeCommand('Other', 'app:other', true);
        $this->app->make(ArtisanRepositoryInterface::class)->load();
        $other = $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\Other')
            ->getModel();

        $this->createRun($test, 1, '2026-10-16 12:01:00', '2026-10-16 12:01:01');
        $this->createRun($other, 1, '2026-10-16 12:02:00', '2026-10-16 12:02:01');
        $this->createRun($test, 1, '2026-10-16 12:03:00', '2026-10-16 12:03:01');
        $this->createRun($test, 0, '2026-10-16 12:04:00', '2026-10-16 12:04:01');
        $last = $this->createRun($test, 1, '2026-10-16 12:05:00', '2026-10-16 12:05:02');

        $response = $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('runs.totals.failed', 4)
            ->assertJsonCount(2, 'runs.errors')
            ->assertJsonPath('runs.errors.0.command', 'app:test')
            ->assertJsonPath('runs.errors.0.failed', 3)
            ->assertJsonPath('runs.errors.0.killed', 0)
            ->assertJsonPath('runs.errors.0.last.id', $last->id)
            ->assertJsonPath('runs.errors.0.last.duration', 2)
            ->assertJsonPath('runs.errors.1.command', 'app:other')
            ->assertJsonPath('runs.errors.1.failed', 1);

        $this->assertSame([3, 1], array_column($response->json('runs.errors'), 'total'));
    }

    public function testUpcoming(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $hourly = $this->createSchedule($model, ['name' => 'Hourly', 'time_method' => 'hourly']);
        $everyMinute = $this->createSchedule($model);
        $this->createSchedule($model, ['run' => false]);

        $this
            ->getJson('/chronos/dashboard/data')
            ->assertOk()
            ->assertJsonPath('schedules.upcoming', [
                [
                    'id' => $everyMinute->id,
                    'name' => null,
                    'command' => 'app:test',
                    'next_run_at' => '2026-10-16T10:01:00+00:00',
                    'lock' => null,
                ],
                [
                    'id' => $hourly->id,
                    'name' => 'Hourly',
                    'command' => 'app:test',
                    'next_run_at' => '2026-10-16T11:00:00+00:00',
                    'lock' => null,
                ],
            ]);
    }

    public function testToken(): void
    {
        config(['chronos.monitoring.dashboard_token' => 'secret']);
        $this->reloadRoutes();

        $this->get('/chronos/dashboard')->assertForbidden();
        $this->getJson('/chronos/dashboard/data')->assertForbidden();
        $this->getJson('/chronos/dashboard/data?token=wrong')->assertForbidden();
        $this->getJson('/chronos/dashboard/data?token=secret')->assertOk();

        $this
            ->get('/chronos/dashboard?token=secret')
            ->assertOk()
            ->assertSee(json_encode(route('chronos.dashboard.data', ['token' => 'secret'])), false);

        // The token of the dashboard does not open health and vice versa.
        $this->getJson('/chronos/health?token=secret')->assertOk();
        config(['chronos.monitoring.health_token' => 'other']);
        $this->reloadRoutes();
        $this->getJson('/chronos/health?token=secret')->assertForbidden();
        $this->getJson('/chronos/dashboard/data?token=other')->assertForbidden();
    }

    public function testWithoutTokenBehindPanelMiddlewares(): void
    {
        config(['chronos.middlewares' => ['auth']]);
        $this->reloadRoutes();

        $this->getJson('/chronos/dashboard/data')->assertUnauthorized();
    }

    public function testLinkInPanel(): void
    {
        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertSee(route('chronos.dashboard'));
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', []);
    }

    private function reloadRoutes(): void
    {
        $this->app['router']->setRoutes(new RouteCollection());
        $this->app->getProvider(ChronosServiceProvider::class)->loadRoutes();
    }

    private function model(): Command
    {
        $this->makeCommand(withChronosTrait: true);

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        return $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\Test')
            ->getModel();
    }

    private function createRun(
        Command $model,
        int $state,
        string $createdAt,
        string $updatedAt,
        array $attributes = [],
    ): CommandRun {
        $run = CommandRun::create([
            'command_id' => $model->id,
            'state' => $state,
            ...$attributes,
        ]);

        DB::table('command_runs')->where('id', $run->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        return $run;
    }

    private function createSchedule(Command $model, array $attributes = []): Schedule
    {
        return Schedule::create([
            'command_id' => $model->id,
            'args' => [],
            'time_method' => 'everyMinute',
            'run' => true,
            ...$attributes,
        ]);
    }
}
