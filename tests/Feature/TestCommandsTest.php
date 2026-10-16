<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Contracts\Console\Kernel;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class TestCommandsTest extends TestCase
{
    public function testTestCommandsAreNotRegisteredOutsideLocal(): void
    {
        $this->app['env'] = 'production';

        $commands = $this->loadCommands(null);

        $this->assertArrayNotHasKey('chronos:test', $commands);
        $this->assertArrayHasKey('chronos:free-logs', $commands);
        $this->assertArrayHasKey('chronos:update-metrics', $commands);
    }

    public function testTestCommandsAreRegisteredInLocal(): void
    {
        $this->app['env'] = 'local';

        $this->assertArrayHasKey('chronos:test', $this->loadCommands(null));
    }

    public function testTestCommandsAreEnabledByConfig(): void
    {
        $this->app['env'] = 'production';

        $this->assertArrayHasKey('chronos:test', $this->loadCommands('true'));
    }

    public function testTestCommandsAreDisabledByConfig(): void
    {
        $this->app['env'] = 'local';

        $this->assertArrayNotHasKey('chronos:test', $this->loadCommands(false));
    }

    protected function tearDown(): void
    {
        // migrations are rolled back without confirmation only outside production
        $this->app['env'] = 'testing';

        parent::tearDown();
    }

    private function loadCommands(
        string|bool|null $testCommands,
    ): array {
        config(['chronos.test_commands' => $testCommands]);

        $kernel = $this
            ->app
            ->make(Kernel::class);

        $kernel->setArtisan(null);

        $this
            ->app
            ->getProvider(ChronosServiceProvider::class)
            ->loadCommands();

        return $kernel->all();
    }
}
