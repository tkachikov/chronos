<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Illuminate\Support\Str;
use Tkachikov\LaravelPulse\CommandHandler;

class PulseInstallCommand extends CommandHandler
{
    protected $signature = 'pulse:install';

    protected $description = 'Install all Laravel Pulse resources';

    protected bool $runInManual = false;

    protected bool $runInSchedule = false;

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->comment('Publishing Laravel Pulse Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'pulse-provider']);

        $this->comment('Publishing Laravel Pulse Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'pulse-config']);

        $this->registerLaravelPulseServiceProvider();

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    protected function registerLaravelPulseServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\LaravelPulseServiceProvider::class')) {
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
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\LaravelPulseServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/LaravelPulseServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/LaravelPulseServiceProvider.php'))
        ));
    }
}
