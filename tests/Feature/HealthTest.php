<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\SchedulerHeartbeatService;

final class HealthTest extends TestCase
{
    public function testOkWithoutSchedules(): void
    {
        $this
            ->getJson('/chronos/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'scheduler' => ['status' => 'ok', 'last_run_at' => null, 'timeout_minutes' => 5],
                'schedules' => ['enabled' => 0, 'missed' => [], 'failed' => 0],
            ]);
    }

    public function testOkWhenSchedulerRuns(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $this->createSchedule();
        $this->app->make(SchedulerHeartbeatService::class)->beat();

        $this->travelTo('2026-10-16 10:01:30');

        $this
            ->getJson('/chronos/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('scheduler.hosts.0.host', gethostname())
            ->assertJsonPath('schedules.enabled', 1);
    }

    public function testFailWhenSchedulerIsStopped(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $schedule = $this->createSchedule(['name' => 'Import #1']);
        $this->app->make(SchedulerHeartbeatService::class)->beat();

        $this->travelTo('2026-10-16 10:10:00');

        $this
            ->getJson('/chronos/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'fail')
            ->assertJsonPath('scheduler.status', 'stopped')
            ->assertJsonPath('schedules.missed.0.id', $schedule->id)
            ->assertJsonPath('schedules.missed.0.name', 'Import #1')
            ->assertJsonPath('schedules.missed.0.command', 'App\\Console\\Commands\\Test');
    }

    public function testToken(): void
    {
        config(['chronos.monitoring.health_token' => 'secret']);
        $this->reloadRoutes();

        $this->getJson('/chronos/health')->assertForbidden();
        $this->getJson('/chronos/health?token=wrong')->assertForbidden();
        $this->getJson('/chronos/health?token=secret')->assertOk();
        $this->getJson('/chronos/health', ['Authorization' => 'Bearer secret'])->assertOk();
    }

    public function testWithoutTokenBehindPanelMiddlewares(): void
    {
        config(['chronos.middlewares' => [DenyMiddleware::class]]);
        $this->reloadRoutes();

        $this->getJson('/chronos/health')->assertForbidden();
    }

    public function testCommand(): void
    {
        $this
            ->artisan('chronos:health')
            ->expectsOutputToContain('Scheduler: ok, last schedule:run: never')
            ->expectsOutputToContain('Schedules: 0 enabled, 0 missed, 0 failed, 0 running')
            ->assertSuccessful();

        $this->travelTo('2026-10-16 10:00:30');
        $this->createSchedule();

        $this->travelTo('2026-10-16 10:10:00');

        $this
            ->artisan('chronos:health')
            ->expectsOutputToContain('Scheduler: stopped')
            ->expectsOutputToContain('missed since 2026-10-16T10:01:00')
            ->assertFailed();
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

    private function createSchedule(array $attributes = []): Schedule
    {
        $this->makeCommand(withChronosTrait: true);

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        $model = $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\Test')
            ->getModel();

        return Schedule::create([
            'command_id' => $model->id,
            'args' => [],
            'time_method' => 'everyMinute',
            'run' => true,
            ...$attributes,
        ]);
    }
}

final class DenyMiddleware
{
    public function handle(Request $request, Closure $next): never
    {
        abort(403);
    }
}
