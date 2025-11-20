<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ChronosServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->artisan('migrate')
            ->run();
    }

    protected function tearDown(): void
    {
        $path = app_path('Console/Commands');
        File::deleteDirectory($path);

        parent::tearDown();
    }

    protected function makeCommand(
        string $name = 'Test',
        string $command = 'app:test',
        bool $withChronosTrait = false,
        ?string $body = null,
    ): void {
        $path = app_path("Console/Commands/$name.php");
        $class = "\\App\\Console\\Commands\\$name";

        $this->artisan('make:command', [
            'name' => $name,
            '--command' => $command,
        ]);

        if ($withChronosTrait || $body) {
            $content = file_get_contents($path);

            if ($withChronosTrait) {
                $content = str_replace(
                    'use Illuminate\Console\Command;',
                    "use Illuminate\Console\Command;\nuse Tkachikov\Chronos\Traits\ChronosRunnerTrait;",
                    $content,
                );
                $content = str_replace(
                    "extends Command\n{",
                    "extends Command\n{\n\tuse ChronosRunnerTrait;\n",
                    $content,
                );
            }

            if ($body) {
                $bodySearch = version_compare(app()->version(), '10.0', '>=')
                    ? '//'
                    : 'return Command::SUCCESS;';
                $content = str_replace(
                    $bodySearch,
                    $body,
                    $content,
                );
            }

            file_put_contents($path, $content);
        }

        require_once $path;
        Artisan::registerCommand(new $class());
    }
}