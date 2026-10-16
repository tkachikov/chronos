<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\SchedulerHeartbeatService;

final class MonitoringPageTest extends TestCase
{
    public function testLastAndNextRun(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $schedule = $this->createSchedule($model);
        $this->app->make(SchedulerHeartbeatService::class)->beat();

        DB::table('schedules')->where('id', $schedule->id)->update([
            'last_run_at' => '2026-10-16 10:00:30',
            'last_finished_at' => '2026-10-16 10:00:31',
            'last_status' => 'failed',
        ]);

        $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            ->assertSeeInOrder(['Last run', 'Next run'])
            ->assertSeeInOrder(['Failed', '2026-10-16 10:00:30', '2026-10-16 10:01:00'])
            ->assertDontSee('Missed since')
            ->assertDontSee('The scheduler is not running');

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertSee('Last run failed');
    }

    public function testMissedAndStoppedScheduler(): void
    {
        $this->travelTo('2026-10-16 10:00:30');
        $model = $this->model();
        $this->createSchedule($model);
        $this->app->make(SchedulerHeartbeatService::class)->beat();

        $this->travelTo('2026-10-16 10:10:00');

        $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            ->assertSee('Missed since 2026-10-16 10:01:00')
            ->assertSee('The scheduler is not running')
            ->assertSee('9 minutes ago');

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertSee('Missed since 2026-10-16 10:01:00')
            ->assertSee('The scheduler is not running');
    }

    public function testNoBannerWithoutEnabledSchedules(): void
    {
        $model = $this->model();
        $this->createSchedule($model, ['run' => false]);

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertDontSee('The scheduler is not running');
    }

    public function testBannerWhenSchedulerHasNeverRun(): void
    {
        $this->createSchedule($this->model());

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertSee('has never run');
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
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
}
