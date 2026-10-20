<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Js;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\RealTime\CacheService;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class EditPageTest extends TestCase
{
    public function testRealTimeUrlsAreGeneratedByRoutes(): void
    {
        $model = $this->model();

        $response = $this
            ->get(route('chronos.edit', $model))
            ->assertOk();

        foreach (['run', 'logs', 'answer', 'sigterm', 'sigkill'] as $name) {
            $url = route("chronos.runInRealTime.$name", $model);

            $response->assertSee((string) Js::from($url), false);
        }

        $response->assertDontSee('`/chronos/', false);
    }

    public function testRealTimeLogsFromOffset(): void
    {
        $model = $this->model();

        $this
            ->app
            ->make(CacheService::class)
            ->set(new RunDto(commandId: $model->id, logs: ['first', 'second', 'third']));

        $this
            ->get(route('chronos.runInRealTime.logs', [$model, 'offset' => 1]))
            ->assertOk()
            ->assertJson([
                'logs' => ['second', 'third'],
                'offset' => 1,
                'status' => false,
            ]);

        $this
            ->get(route('chronos.runInRealTime.logs', $model))
            ->assertJson([
                'logs' => ['first', 'second', 'third'],
                'offset' => 0,
            ]);
    }

    public function testOverviewAttentionAndRunsFilter(): void
    {
        $model = $this->model();
        $ok = CommandRun::create(['command_id' => $model->id, 'state' => 0, 'memory' => '30 MB']);
        $failed = CommandRun::create(['command_id' => $model->id, 'state' => 1, 'memory' => '32 MB']);

        $html = $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            // The tiles and the issue of the last run
            ->assertSeeInOrder(['Last run', 'Failed', 'Next run', 'Not scheduled', 'Runs', '1 failed'])
            ->assertSee('The last run failed')
            ->assertSee('href="#run-' . $failed->id . '"', false)
            // Both runs, the latest one is open; the form of a schedule is hidden
            ->assertSee('id="run-' . $ok->id . '"', false)
            ->assertSee('Failed <span class="cx-n">1</span>', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="run-' . $failed->id . '"\s+open/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="scheduleModal"[^>]*data-open/', $html);
        $this->assertStringContainsString('data-bs-target="#scheduleModal"', $html);
        $this->assertStringContainsString('New schedule', $html);

        // Only the failed runs
        $this
            ->get(route('chronos.edit', [$model, 'runs' => 'failed']))
            ->assertOk()
            ->assertSee('id="run-' . $failed->id . '"', false)
            ->assertDontSee('id="run-' . $ok->id . '"', false);

        // The form is open under the edited schedule (the schedules are cached on the model of the manager)
        $model->unsetRelation('schedules');
        $schedule = Schedule::create([
            'command_id' => $model->id,
            'name' => 'Nightly',
            'args' => [],
            'time_method' => 'everyMinute',
            'run' => true,
        ]);

        $html = $this
            ->get(route('chronos.edit', [$model, 'schedule' => $schedule->id]))
            ->assertOk()
            ->assertSee('Edit schedule')
            ->assertSeeInOrder(['Next run', 'from now', 'Nightly'])
            ->getContent();

        $this->assertStringContainsString('class="cx-sched active" id="schedule-' . $schedule->id . '"', $html);
        $this->assertMatchesRegularExpression('/id="scheduleModal"[^>]*data-open/', $html);
        $this->assertStringContainsString('value="Nightly"', $html);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
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
