<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command;

final class CommandManagerTest extends TestCase
{
    public function testGettingCommandsFromStorage(): void
    {
        $exists = $this
            ->app
            ->make(DatabaseHelper::class)
            ->hasTable(Command::class);

        $this->assertTrue($exists);
        $this->assertEquals(0, Command::count());

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $decorators = $manager->get();

        $countCommands = Command::count();

        $this->assertNotEquals(0, count($decorators));

        foreach ($decorators as $decorator) {
            $this->assertInstanceOf(Command::class, $decorator->getModel());
        }

        foreach (Artisan::all() as $command) {
            $this->assertDatabaseHas(
                (new Command())->getTable(),
                ['class' => $command::class],
            );
        }

        $this->assertEquals(0, $manager->getApps()->count());
        $this->assertNotEquals(0, $manager->getSystems()->count());
        $this->assertNotEquals(0, $manager->getChronos()->count());
        $this->assertEquals($countCommands, Command::count());
    }
}
