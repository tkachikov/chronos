<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Js;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class EditPageTest extends TestCase
{
    public function testRealTimeUrlsAreGeneratedByRoutes(): void
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

        $response = $this
            ->get(route('chronos.edit', $model))
            ->assertOk();

        foreach (['run', 'logs', 'answer', 'sigterm', 'sigkill'] as $name) {
            $url = route("chronos.runInRealTime.$name", $model);

            $response->assertSee((string) Js::from($url), false);
        }

        $response->assertDontSee('`/chronos/', false);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
    }
}
