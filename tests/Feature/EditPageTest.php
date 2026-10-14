<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Js;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
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
