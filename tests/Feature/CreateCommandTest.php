<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Contracts\Container\BindingResolutionException;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;

final class CreateCommandTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testEmptyAppCommands(): void
    {
        $manager = $this
            ->app
            ->make(CommandManager::class);

        $this->assertCount(0, $manager->getApps());
    }

    /**
     * @throws BindingResolutionException
     */
    public function testEmptyAppCommandsAfterCreated(): void
    {
        $this->makeCommand();

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $this->assertCount(0, $manager->getApps());
    }

    /**
     * @throws BindingResolutionException
     */
    public function testNotEmptyAppCommands(): void
    {
        $this->makeCommand();

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $this->assertCount(1, $manager->getApps());
        $this->assertDatabaseHas(
            (new Command())->getTable(),
            ['class' => 'App\Console\Commands\Test'],
        );
        $this->assertSame(
            'App\Console\Commands\Test',
            $manager
                ->getApps()
                ->first()
                ->getClassName(),
        );
    }
}
