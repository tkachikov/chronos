<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tkachikov\LaravelPulse\Console\Commands\PulseTestCommand;
use Tkachikov\LaravelPulse\Console\Commands\PulseInstallCommand;
use Tkachikov\LaravelPulse\Console\Commands\PulseFreeLogsCommand;
use Tkachikov\LaravelPulse\Console\Commands\PulseUpdateMetricsCommand;

class LaravelPulseServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadCommands();
        $this->loadPublishing();
        $this->loadMigrations();
    }

    /**
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * @return void
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PulseInstallCommand::class,
            ]);
        }
        $this->commands([
            PulseTestCommand::class,
            PulseFreeLogsCommand::class,
            PulseUpdateMetricsCommand::class,
        ]);
    }

    /**
     * @return void
     */
    public function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    /**
     * @return void
     */
    public function loadPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/pulse.php' => config_path('pulse.php'),
            ], 'pulse-config');
            $this->publishes([
                __DIR__ . '/../../stubs/LaravelPulseServiceProvider.stub' => app_path('Providers/LaravelPulseServiceProvider.php'),
            ], 'pulse-provider');
        }
    }

    /**
     * @return void
     */
    public function loadRoutes(): void
    {
        Route::middlewareGroup('pulse', config('pulse.middlewares'));
        Route::group([
            'domain' => config('pulse.domain'),
            'prefix' => 'pulse',
            'middleware' => 'pulse',
            'namespace' => 'Tkachikov\LaravelPulse\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });
    }

    /**
     * @return void
     */
    public function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'pulse');
    }
}
