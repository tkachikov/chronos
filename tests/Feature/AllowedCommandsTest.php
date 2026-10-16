<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Bus;
use Tkachikov\Chronos\Jobs\CommandRunJob;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\CommandService;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class AllowedCommandsTest extends TestCase
{
    public function testSystemCommandsAreDisabledByDefault(): void
    {
        $service = $this->service();

        $this->assertFalse($service->exists(SystemTestCommand::class));
        $this->assertFalse($service->getBaseCommands()->has(SystemTestCommand::class));
    }

    public function testSystemCommandIsAllowedByName(): void
    {
        config(['chronos.system_commands' => ['system:test']]);

        $service = $this->service();

        $this->assertTrue($service->exists(SystemTestCommand::class));
        $this->assertTrue($service->getBaseCommands()->has(SystemTestCommand::class));
    }

    public function testSystemCommandIsAllowedByClass(): void
    {
        config(['chronos.system_commands' => [SystemTestCommand::class]]);

        $this->assertTrue($this->service()->exists(SystemTestCommand::class));
    }

    public function testAppCommandIsAllowed(): void
    {
        $this->makeCommand();
        $this->loadCommands();

        $this->assertTrue($this->service()->exists('App\\Console\\Commands\\Test'));
    }

    public function testJobDoesNotRunDisabledSystemCommand(): void
    {
        $service = $this->service();

        (new CommandRunJob(SystemTestCommand::class))->handle($service);

        $this->assertFalse(SystemTestCommand::$handled);
    }

    public function testJobRunsAllowedSystemCommand(): void
    {
        config(['chronos.system_commands' => ['system:test']]);

        $service = $this->service();

        (new CommandRunJob(SystemTestCommand::class))->handle($service);

        $this->assertTrue(SystemTestCommand::$handled);
    }

    public function testRoutesOfDisabledSystemCommandAreNotFound(): void
    {
        Bus::fake();

        $model = $this->model();

        $this
            ->get(route('chronos.edit', $model))
            ->assertNotFound();

        $this
            ->post(route('chronos.run', $model))
            ->assertNotFound();

        $this
            ->post(route('chronos.runInRealTime.run', $model))
            ->assertNotFound();

        Bus::assertNotDispatched(CommandRunJob::class);
    }

    public function testRoutesOfAllowedSystemCommandAreAvailable(): void
    {
        config(['chronos.system_commands' => ['system:test']]);

        Bus::fake();

        $this
            ->post(route('chronos.run', $this->model()))
            ->assertRedirect();

        Bus::assertDispatched(CommandRunJob::class);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        SystemTestCommand::$handled = false;

        $this
            ->app
            ->make(Kernel::class)
            ->registerCommand(new SystemTestCommand());

        $this->loadCommands();
    }

    private function loadCommands(): void
    {
        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();
    }

    private function service(): CommandService
    {
        return $this
            ->app
            ->make(CommandService::class);
    }

    private function model(): CommandModel
    {
        return $this
            ->app
            ->make(CommandManager::class)
            ->getByClass(SystemTestCommand::class)
            ->getModel();
    }
}

final class SystemTestCommand extends Command
{
    public static bool $handled = false;

    protected $signature = 'system:test';

    public function handle(): void
    {
        self::$handled = true;
    }
}
