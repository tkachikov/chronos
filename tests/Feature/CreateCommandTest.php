<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Repositories\CommandRepositoryInterface;

final class CreateCommandTest extends TestCase
{
    public function testEmptyAppCommands(): void
    {
        $this
            ->app
            ->make(CommandRepositoryInterface::class)
            ->load();

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $this->assertCount(0, $manager->getApps());
    }

    public function testNotEmptyAppCommands(): void
    {
        $this
            ->app
            ->make(CommandRepositoryInterface::class)
            ->load();

        $this->makeCommand();

        $manager = $this->app->make(CommandManager::class);

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
