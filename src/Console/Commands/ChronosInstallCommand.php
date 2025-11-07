<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosInstallCommand extends Command
{
    protected $signature = 'chronos:install';

    protected $description = 'Install all Chronos resources';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'chronos-provider']);

        $this->call('vendor:publish', ['--tag' => 'chronos-config']);

        $this->registerServiceProvider();
        $this->info("\tRegistered service provider");

        $this->call('migrate', [
            '--path' => 'vendor/tkachikov/chronos/database/migrations',
            '--force' => true,
        ]);

        return self::SUCCESS;
    }

    protected function registerServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\ChronosServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\ChronosServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/ChronosServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/ChronosServiceProvider.php'))
        ));
    }
}
